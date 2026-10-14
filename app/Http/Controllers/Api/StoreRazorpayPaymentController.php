<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\EmployeeCommission;
use App\Models\DeliveryRate;
use App\Models\Payment;
use App\Models\AlternativeAddress;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StoreWallet;
use App\Models\StoreWalletTransaction;
use App\Models\OrderItemCancellation;
use App\Models\Coupon;
class StoreRazorpayPaymentController extends Controller
{
    protected $isTest = false; // true = test | false = live
    public function createOrder(Request $request)
    {
        try {
            $user = $request->user();
            $request->validate([
                'coupon_code' => 'nullable|string',
                'address_id' => 'nullable|exists:alternative_addresses,id',
                'wallet_amount' => 'nullable|numeric|min:0',
            ]);

            $walletRequestedCents = $this->moneyToCents($request->wallet_amount ?? 0);
            $cart = Cart::where('user_id', $user->id)->firstOrFail();
            $items = CartItem::where('cart_id', $cart->id)->get();
            if ($items->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'Cart empty'], 422);
            }

            $validatedCart = $this->validateCartItems($items);
            $products = $validatedCart['products'];
            $subtotalCents = $this->moneyToCents($validatedCart['subtotal']);
            $subtotal = $subtotalCents / 100;
            $originalPricing = $this->calculateProductTaxDetails($items, $products, 0, $subtotalCents, 'igst');
            $originalTaxableCents = $originalPricing['original_taxable_cents'];

            $coupon = null;
            $couponId = null;
            $discountCents = 0;
            if ($request->filled('coupon_code')) {
                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('status', 1)
                    ->whereDate('expiry_date', '>=', now())
                    ->first();
                if (!$coupon) {
                    return response()->json(['status' => false, 'message' => 'Invalid coupon'], 422);
                }
                if (!in_array($coupon->payment_type, ['razorpay', 'both'], true)) {
                    return response()->json(['status' => false, 'message' => 'This coupon is not applicable for Razorpay orders'], 422);
                }
                $minimumCents = $this->moneyToCents($coupon->min_amount ?? 0);
                if ($minimumCents > 0 && $subtotalCents < $minimumCents) {
                    return response()->json(['status' => false, 'message' => 'Coupon minimum amount not met'], 422);
                }
                if ($coupon->discount_type === 'flat') {
                    $discountCents = $this->moneyToCents($coupon->discount_value ?? 0);
                } else {
                    $discountRate = $this->normalizeRate($coupon->discount_value ?? 0);
                    $discountCents = (int) round(($originalTaxableCents * $discountRate) / 100, 0, PHP_ROUND_HALF_UP);
                    $maxDiscountCents = $this->moneyToCents($coupon->max_discount ?? 0);
                    if ($maxDiscountCents > 0) {
                        $discountCents = min($discountCents, $maxDiscountCents);
                    }
                }
                $discountCents = max(0, min($discountCents, $originalTaxableCents));
                $couponId = $coupon->id;
            }

            $productPricing = $this->calculateProductTaxDetails($items, $products, $discountCents, $subtotalCents, 'igst');
            $discountCents = $productPricing['discount_cents'];
            $discount = $discountCents / 100;
            $afterDiscountCents = $productPricing['total_cents'];

            $address = null;
            $deliveryChargeCents = 0;
            if ($request->filled('address_id')) {
                $address = AlternativeAddress::where('id', $request->address_id)
                    ->where('user_id', $user->id)
                    ->first();
                if (!$address) {
                    return response()->json(['status' => false, 'message' => 'Invalid delivery address'], 422);
                }
                if ($address->state) {
                    $deliveryRate = DeliveryRate::where('state', $address->state)
                        ->where('status', 1)
                        ->first();
                    if ($deliveryRate) {
                        $deliveryChargeCents = $subtotalCents >= 80000
                            ? 0
                            : $this->moneyToCents($deliveryRate->delivery_charge ?? 0);
                    }
                }
            }

            $wallet = StoreWallet::where('user_id', $user->id)->first();
            if (!$wallet) {
                $wallet = StoreWallet::create(['user_id' => $user->id, 'balance' => 0]);
            }
            $walletBalanceCents = $this->moneyToCents($wallet->balance ?? 0);
            if ($walletRequestedCents > $walletBalanceCents) {
                return response()->json(['status' => false, 'message' => 'Insufficient wallet balance'], 422);
            }

            $orderBeforeWalletCents = $afterDiscountCents + $deliveryChargeCents;
            $walletUsedCents = min($walletRequestedCents, $orderBeforeWalletCents);
            $finalAmountCents = max(0, $orderBeforeWalletCents - $walletUsedCents);
            $breakdown = [
                'subtotal' => $this->formatCents($subtotalCents),
                'original_taxable_amount' => $this->formatCents($originalTaxableCents),
                'coupon_discount' => $this->formatCents($discountCents),
                'coupon_discount_taxable_amount' => $this->formatCents($discountCents),
                'net_taxable_amount' => $this->formatCents($productPricing['taxable_cents']),
                'product_gst_amount' => $this->formatCents($productPricing['gst_cents']),
                'product_gst_savings' => $this->formatCents(max(0, $productPricing['original_gst_cents'] - $productPricing['gst_cents'])),
                'discounted_product_total' => $this->formatCents($afterDiscountCents),
                'delivery_charge' => $this->formatCents($deliveryChargeCents),
                'wallet_used' => $this->formatCents($walletUsedCents),
                'final_amount' => $this->formatCents($finalAmountCents),
            ];

            if ($finalAmountCents <= 0) {
                return response()->json([
                    'status' => true,
                    'payment_mode' => 'wallet_only',
                    'order_id' => null,
                    'coupon_id' => $couponId,
                    'breakdown' => array_merge($breakdown, ['final_amount' => '0.00']),
                ]);
            }

            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            $gatewayOrder = $api->order->create([
                'receipt' => 'store_' . uniqid(),
                'amount' => $finalAmountCents,
                'currency' => 'INR',
                'notes' => [
                    'user_id' => (string) $user->id,
                    'address_id' => (string) ($request->address_id ?? ''),
                    'subtotal' => $this->formatCents($subtotalCents),
                    'discount' => $this->formatCents($discountCents),
                    'coupon_discount_taxable_amount' => $this->formatCents($discountCents),
                    'original_taxable_amount' => $this->formatCents($originalTaxableCents),
                    'net_taxable_amount' => $this->formatCents($productPricing['taxable_cents']),
                    'product_gst_amount' => $this->formatCents($productPricing['gst_cents']),
                    'delivery_charge' => $this->formatCents($deliveryChargeCents),
                    'wallet_used' => $this->formatCents($walletUsedCents),
                    'final_amount' => $this->formatCents($finalAmountCents),
                ],
            ]);

            return response()->json([
                'status' => true,
                'order_id' => $gatewayOrder['id'],
                'coupon_id' => $couponId,
                'breakdown' => $breakdown,
            ]);
        } catch (\Exception $e) {
            Log::error('STORE CREATE ORDER ERROR', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => $this->isTest ? $e->getMessage() : 'Unable to create order',
            ], 422);
        }
    }
    public function verify(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'nullable',
            'razorpay_payment_id' => 'nullable',
            'razorpay_signature' => 'nullable',
            'address_id' => 'nullable|exists:alternative_addresses,id',
            'coupon_code' => 'nullable',
            'wallet_amount' => 'nullable|numeric|min:0'
        ]);
        DB::beginTransaction();
        try {
            $user = $request->user();
            $walletInputCents = $this->moneyToCents($request->wallet_amount ?? 0);
            $cart = Cart::where('user_id', $user->id)->firstOrFail();
            $items = CartItem::where('cart_id', $cart->id)->get();
            if ($items->isEmpty()) {
                throw new \Exception('Cart empty');
            }

            $productIds = $items->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');
            $subtotalCents = 0;
            foreach ($items as $item) {
                $product = $products[$item->product_id] ?? null;
                if (!$product || (($product->status ?? 1) != 1)) {
                    throw new \Exception('Product is unavailable or was removed');
                }
                $quantity = (int) $item->quantity;
                if ($quantity <= 0) {
                    throw new \Exception($product->name . ' has invalid quantity');
                }
                if ($product->stock_qty < $quantity) {
                    throw new \Exception($product->name . ' only ' . $product->stock_qty . ' left in stock');
                }
                $unitPriceCents = $this->moneyToCents($item->price_at_time);
                $lineTotalCents = $this->moneyToCents($item->total_price);
                if ($unitPriceCents <= 0 || ($unitPriceCents * $quantity) !== $lineTotalCents) {
                    throw new \Exception($product->name . ' cart amount mismatch');
                }
                $subtotalCents += $lineTotalCents;
            }
            $subtotal = $subtotalCents / 100;

            $originalPricing = $this->calculateProductTaxDetails($items, $products, 0, $subtotalCents, 'igst');
            $originalTaxableCents = $originalPricing['original_taxable_cents'];
            $coupon = null;
            $couponId = null;
            $discountCents = 0;
            if ($request->filled('coupon_code')) {
                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('status', 1)
                    ->whereDate('expiry_date', '>=', now())
                    ->lockForUpdate()
                    ->first();
                if (!$coupon) {
                    throw new \Exception('Invalid coupon');
                }
                if (!in_array($coupon->payment_type, ['razorpay', 'both'], true)) {
                    throw new \Exception('This coupon is not applicable for Razorpay orders');
                }
                $minimumCents = $this->moneyToCents($coupon->min_amount ?? 0);
                if ($minimumCents > 0 && $subtotalCents < $minimumCents) {
                    throw new \Exception('Coupon minimum amount not met');
                }
                if ($coupon->discount_type === 'flat') {
                    $discountCents = $this->moneyToCents($coupon->discount_value ?? 0);
                } else {
                    $discountRate = $this->normalizeRate($coupon->discount_value ?? 0);
                    $discountCents = (int) round(($originalTaxableCents * $discountRate) / 100, 0, PHP_ROUND_HALF_UP);
                    $maxDiscountCents = $this->moneyToCents($coupon->max_discount ?? 0);
                    if ($maxDiscountCents > 0) {
                        $discountCents = min($discountCents, $maxDiscountCents);
                    }
                }
                $discountCents = max(0, min($discountCents, $originalTaxableCents));
                $couponId = $coupon->id;
            }

            $address = null;
            if ($request->filled('address_id')) {
                $address = AlternativeAddress::where('id', $request->address_id)
                    ->where('user_id', $user->id)
                    ->first();
                if (!$address) {
                    throw new \Exception('Invalid delivery address');
                }
            }
            $sellerState = 'Delhi';
            $taxType = ($address && strtolower(trim($address->state ?? '')) === strtolower(trim($sellerState)))
                ? 'cgst_sgst'
                : 'igst';

            $productTaxData = $this->calculateProductTaxDetails(
                $items,
                $products,
                $discountCents,
                $subtotalCents,
                $taxType
            );
            $discountCents = $productTaxData['discount_cents'];
            $discount = $discountCents / 100;
            $afterDiscountCents = $productTaxData['total_cents'];
            $afterDiscount = $afterDiscountCents / 100;

            $deliveryChargeCents = 0;
            if ($address && $address->state) {
                $deliveryRate = DeliveryRate::where('state', $address->state)->where('status', 1)->first();
                if ($deliveryRate) {
                    $deliveryChargeCents = $subtotalCents >= 80000
                        ? 0
                        : $this->moneyToCents($deliveryRate->delivery_charge ?? 0);
                }
            }
            $deliveryCharge = $deliveryChargeCents / 100;

            $wallet = StoreWallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = StoreWallet::create(['user_id' => $user->id, 'balance' => 0]);
            }
            $walletBalanceCents = $this->moneyToCents($wallet->balance ?? 0);
            if ($walletInputCents > $walletBalanceCents) {
                throw new \Exception('Invalid wallet usage');
            }
            $orderBeforeWalletCents = $afterDiscountCents + $deliveryChargeCents;
            $walletUsedCents = min($walletInputCents, $orderBeforeWalletCents);
            $walletUsed = $walletUsedCents / 100;
            $finalAmountCents = max(0, $orderBeforeWalletCents - $walletUsedCents);
            $finalAmount = $finalAmountCents / 100;
            if ($finalAmountCents > 0 && !$request->razorpay_payment_id) {
                throw new \Exception('Payment required');
            }
            // PAYMENT
            $payment = null;
            $paymentData = null;
            $paymentMode = 'wallet_only';
            if ($finalAmount > 0) {
                $existing = Payment::where('transaction_id', $request->razorpay_payment_id)->first();
                if ($existing) {
                    DB::commit();
                    return response()->json(['status' => true, 'message' => 'Already processed']);
                }
                if (!$this->isTest) {
                    $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
                    $api->utility->verifyPaymentSignature([
                        'razorpay_order_id' => $request->razorpay_order_id,
                        'razorpay_payment_id' => $request->razorpay_payment_id,
                        'razorpay_signature' => $request->razorpay_signature
                    ]);
                    $paymentData = $api->payment->fetch($request->razorpay_payment_id);
                    if (($paymentData['status'] ?? '') !== 'captured') {
                        throw new \Exception('Payment not captured');
                    }
                    $gatewayAmount = (int) ($paymentData['amount'] ?? 0);
                    $expectedGatewayAmount = $finalAmountCents;
                    if ($gatewayAmount !== $expectedGatewayAmount) {
                        throw new \Exception('Payment amount mismatch');
                    }
                    $paymentMode = $paymentData['method'] ?? 'online';
                } else {
                    $paymentData = $request->all();
                    $paymentMode = 'test';
                }
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'platform' => 'astrotring_store',
                    'order_id' => null,
                    'payment_gateway' => 'razorpay',
                    'transaction_id' => $request->razorpay_payment_id,
                    'amount' => number_format($finalAmount, 2, '.', ''),
                    'currency' => 'INR',
                    'payment_status' => 'success',
                    'payment_mode' => $paymentMode,
                    'customer_email' => $user->email,
                    'customer_phone' => trim(($user->country_code ?? '') . ($user->mobile ?? '')),
                    'payment_request_data' => [
                        'subtotal' => number_format((float) $subtotal, 2, '.', ''),
                        'discount' => $this->formatCents($discountCents),
                        'coupon_discount_taxable_amount' => $this->formatCents($discountCents),
                        'original_taxable_amount' => $this->formatCents($productTaxData['original_taxable_cents']),
                        'net_taxable_amount' => $this->formatCents($productTaxData['taxable_cents']),
                        'product_gst_amount' => $this->formatCents($productTaxData['gst_cents']),
                        'wallet_requested' => $this->formatCents($walletInputCents),
                        'wallet_used' => number_format($walletUsed, 2, '.', ''),
                        'final_amount' => number_format($finalAmount, 2, '.', ''),
                        'delivery_charge' => number_format($deliveryCharge, 2, '.', ''),
                        'coupon_code' => $request->coupon_code
                    ],
                    'payment_response_data' => $paymentData
                ]);
            }
            $shippingCgstAmount = 0.00;
            $shippingSgstAmount = 0.00;
            $shippingIgstAmount = 0.00;
            $productTax = $productTaxData['gst_cents'] / 100;
            $productTaxableAmount = $productTaxData['taxable_cents'] / 100;
            $productCgstAmount = $productTaxData['cgst_cents'] / 100;
            $productSgstAmount = $productTaxData['sgst_cents'] / 100;
            $productIgstAmount = $productTaxData['igst_cents'] / 100;
            $itemTaxDetails = $productTaxData['items'];
            $hsnCodes = $productTaxData['hsn_codes'];
            $hsnCode = implode(',', $hsnCodes);
            /*
            |--------------------------------------------------------------------------
            | SHIPPING GST - 18%
            |--------------------------------------------------------------------------
            */
            $shippingGstRate = 18.00;
            $shippingTax = $this->calculateInclusiveGst(
                round((float) $deliveryCharge, 2),
                $shippingGstRate
            );
            $shippingTaxable = round(
                (float) $deliveryCharge - $shippingTax,
                2
            );
            if ($taxType === 'cgst_sgst') {
                $shippingCgstAmount = round(
                    $shippingTax / 2,
                    2
                );
                $shippingSgstAmount = round(
                    $shippingTax - $shippingCgstAmount,
                    2
                );
            } else {
                $shippingIgstAmount = $shippingTax;
            }
            /*
            |--------------------------------------------------------------------------
            | ORDER-LEVEL GST SUMMARY
            |--------------------------------------------------------------------------
            */
            $taxableAmount = round(
                $productTaxableAmount +
                $shippingTaxable,
                2
            );
            $cgstAmount = round(
                $productCgstAmount +
                $shippingCgstAmount,
                2
            );
            $sgstAmount = round(
                $productSgstAmount +
                $shippingSgstAmount,
                2
            );
            $igstAmount = round(
                $productIgstAmount +
                $shippingIgstAmount,
                2
            );
            $totalGstAmount = round(
                $cgstAmount +
                $sgstAmount +
                $igstAmount,
                2
            );
            /*
            |--------------------------------------------------------------------------
            | EFFECTIVE PRODUCT GST RATE
            |--------------------------------------------------------------------------
            */
            $gstRate = $productTaxableAmount > 0
                ? round(
                    ($productTax / $productTaxableAmount) * 100,
                    2,
                    PHP_ROUND_HALF_UP
                )
                : 0.00;
            $gstRate = number_format(
                $gstRate,
                2,
                '.',
                ''
            );
            /* STOCK UPDATE */
            foreach ($items as $item) {
                $product = $products[$item->product_id] ?? null;
                if (!$product) {
                    throw new \Exception('Product not found');
                }
                $newStock = (int) $product->stock_qty - (int) $item->quantity;
                $status = 'in_stock';
                if ($newStock <= 0) {
                    $status = 'out_of_stock';
                } elseif ($newStock <= 5) {
                    $status = 'few_left';
                }
                $product->update([
                    'stock_qty' => $newStock,
                    'stock_status' => $status,
                ]);
            }
            $start = (int) env('INVOICE_START_NUMBER', 1);
            $usedNumbers = Order::whereNotNull('invoice_sequence')
                ->orderBy('invoice_sequence')
                ->pluck('invoice_sequence')
                ->toArray();
            $nextInvoiceSequence = $start;
            foreach ($usedNumbers as $number) {
                if ($number == $nextInvoiceSequence) {
                    $nextInvoiceSequence++;
                } elseif ($number > $nextInvoiceSequence) {
                    break;
                }
            }
            $totalOrderAmountCents = $afterDiscountCents + $deliveryChargeCents;
            $totalOrderAmount = $totalOrderAmountCents / 100;
            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $couponId,
                'payment_id' => $payment ? $payment->id : null,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'invoice_sequence' => $nextInvoiceSequence,
                'invoice_number' => 'AT-' . str_pad(
                    $nextInvoiceSequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),
                'hsn_code' => $hsnCode,
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'discount' => number_format($discount, 2, '.', ''),
                'wallet_used' => number_format($walletUsed, 2, '.', ''),
                'delivery_charge' => number_format($deliveryCharge, 2, '.', ''),
                // Wallet + Razorpay together pay the whole order amount.
                'paid_amount' => number_format($totalOrderAmount, 2, '.', ''),
                'total_amount' => number_format($totalOrderAmount, 2, '.', ''),
                'price_breakdown' => [
                    'subtotal' => number_format($subtotal, 2, '.', ''),
                    'coupon_discount' => $this->formatCents($discountCents),
                    'coupon_discount_taxable_amount' => $this->formatCents($discountCents),
                    'product_gross_amount' => $this->formatCents($subtotalCents),
                    'product_original_taxable_amount' => $this->formatCents($productTaxData['original_taxable_cents']),
                    'product_original_gst_amount' => $this->formatCents($productTaxData['original_gst_cents']),
                    'product_net_taxable_amount' => $this->formatCents($productTaxData['taxable_cents']),
                    'product_gst_savings' => $this->formatCents(max(0, $productTaxData['original_gst_cents'] - $productTaxData['gst_cents'])),
                    'product_net_amount' => $this->formatCents($afterDiscountCents),
                    'delivery_charge' => number_format($deliveryCharge, 2, '.', ''),
                    'wallet_used' => number_format($walletUsed, 2, '.', ''),
                    'product_gst_amount' => number_format($productTax, 2, '.', ''),
                    'product_taxable_amount' => number_format($productTaxableAmount, 2, '.', ''),
                    'item_tax_details' => [],
                    'shipping_gst_rate' => number_format($shippingGstRate, 2, '.', ''),
                    'shipping_gst_amount' => number_format($shippingTax, 2, '.', ''),
                    'shipping_taxable_amount' => number_format($shippingTaxable, 2, '.', ''),
                    'taxable_amount' => number_format($taxableAmount, 2, '.', ''),
                    'gst_rate' => $gstRate,
                    'tax_type' => $taxType,
                    'product_cgst_amount' => number_format($productCgstAmount, 2, '.', ''),
                    'product_sgst_amount' => number_format($productSgstAmount, 2, '.', ''),
                    'product_igst_amount' => number_format($productIgstAmount, 2, '.', ''),
                    'shipping_cgst_amount' => number_format($shippingCgstAmount, 2, '.', ''),
                    'shipping_sgst_amount' => number_format($shippingSgstAmount, 2, '.', ''),
                    'shipping_igst_amount' => number_format($shippingIgstAmount, 2, '.', ''),
                    'cgst_amount' => number_format($cgstAmount, 2, '.', ''),
                    'sgst_amount' => number_format($sgstAmount, 2, '.', ''),
                    'igst_amount' => number_format($igstAmount, 2, '.', ''),
                    'total_gst_amount' => number_format($totalGstAmount, 2, '.', ''),
                    'paid_online' => number_format($finalAmount, 2, '.', ''),
                    'paid_total' => number_format($totalOrderAmount, 2, '.', ''),
                    'final_amount' => number_format($totalOrderAmount, 2, '.', ''),
                ],
                'address_id' => $request->address_id,
                'name' => $address->name ?? null,
                'email' => $address->email ?? $user->email,
                'mobile' => $address->mobile ?? null,
                'alternative_mobile' => $address->alternative_mobile ?? null,
                'city' => $address->city ?? null,
                'state_code' => $address->state_code ?? null,
                'state' => $address->state ?? null,
                'country' => $address->country ?? 'India',
                'address' => $address->address ?? null,
                'pincode' => $address->pincode ?? null,
                'taxable_amount' => number_format($taxableAmount, 2, '.', ''),
                'gst_rate' => $gstRate,
                'cgst_amount' => number_format($cgstAmount, 2, '.', ''),
                'sgst_amount' => number_format($sgstAmount, 2, '.', ''),
                'igst_amount' => number_format($igstAmount, 2, '.', ''),
                'tax_type' => $taxType,
                'advance_paid_amount' => '0.00',
                'remaining_cod_amount' => '0.00',
                'is_cod_advance' => false,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            /*
            |--------------------------------------------------------------------------
            | Employee Commission
            |--------------------------------------------------------------------------
            */
            if (
                $couponId &&
                $coupon &&
                $coupon->employee_id &&
                $coupon->employee_id != 1
            ) {
                $employee = $coupon->employee;
                $percentage = $employee?->commission_percentage ?? 0;
                $percentage = round(
                    (float) $percentage,
                    2
                );
                $orderAmount = round(
                    (float) $order->total_amount,
                    2
                );
                $commissionAmount = round(
                    ($orderAmount * $percentage) / 100,
                    2,
                    PHP_ROUND_HALF_UP
                );
                EmployeeCommission::create([
                    'employee_id' => $coupon->employee_id,
                    'order_id' => $order->id,
                    'coupon_id' => $coupon->id,
                    'order_amount' => number_format(
                        $orderAmount,
                        2,
                        '.',
                        ''
                    ),
                    'commission_percentage' => number_format(
                        $percentage,
                        2,
                        '.',
                        ''
                    ),
                    'commission_amount' => number_format(
                        $commissionAmount,
                        2,
                        '.',
                        ''
                    ),
                    'status' => 'delivery_pending',
                ]);
            }
            if ($payment) {
                $payment->update([
                    'order_id' => $order->id,
                ]);
            }
            $walletTransaction = null;
            // WALLET DEDUCT AFTER ORDER CREATE
            if ($walletUsed > 0) {
                $wallet->refresh();
                if ($wallet->balance < $walletUsed) {
                    throw new \Exception('Wallet changed, retry');
                }
                $before = round((float) $wallet->balance, 2);
                $after = round($before - $walletUsed, 2);
                $wallet->update([
                    'balance' => number_format($after, 2, '.', ''),
                    'total_spent' => number_format((float) $wallet->total_spent + $walletUsed, 2, '.', '')
                ]);
                // StoreWalletTransaction::create([
                $walletTransaction = StoreWalletTransaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id, // âœ… FIXED
                    'type' => 'debit',
                    'amount' => number_format($walletUsed, 2, '.', ''),
                    'source' => 'order_payment',
                    'balance_before' => number_format($before, 2, '.', ''),
                    'balance_after' => number_format($after, 2, '.', ''),
                    'note' => 'Wallet used in order #' . $order->id
                ]);
            }
                $totalWeight = 0;
                $maxLength = 0;
                $maxBreadth = 0;
                $totalHeight = 0;
                $orderItemInvoiceDetails = [];
                foreach ($items as $item) {
                $product = $products[$item->product_id] ?? null;
                if (!$product) {
                    throw new \Exception('Product not found');
                }
                $totalWeight += ((float) ($product->weight ?? 0) * (int) $item->quantity);
                $maxLength = max($maxLength, (float) ($product->length ?? 0));
                $maxBreadth = max($maxBreadth, (float) ($product->breadth ?? 0));
                $totalHeight += ((float) ($product->height ?? 0) * (int) $item->quantity);
                $tax = $itemTaxDetails[$item->id] ?? null;
                if (!$tax) {
                    throw new \Exception('GST calculation missing for order item');
                }
                $orderItemRecord = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $product->name ?? '',
                    'product_slug' => $product->slug ?? '',
                    'product_image' => $product->image ?? '',
                    'ratti' => $item->ratti !== null
                        ? number_format((float) $item->ratti, 2, '.', '')
                        : null,
                    'quantity' => (int) $item->quantity,
                    'price' => number_format((float) $item->price_at_time, 2, '.', ''),
                    'total' => $this->formatCents($tax['line_total_cents']),
                    'weight' => $product->weight !== null
                        ? number_format((float) $product->weight, 2, '.', '')
                        : null,
                    'length' => $product->length !== null
                        ? number_format((float) $product->length, 2, '.', '')
                        : null,
                    'breadth' => $product->breadth !== null
                        ? number_format((float) $product->breadth, 2, '.', '')
                        : null,
                    'height' => $product->height !== null
                        ? number_format((float) $product->height, 2, '.', '')
                        : null,
                    'gst_rate' => $tax['gst_rate'],
                    'gst_amount' => number_format($tax['gst_amount'], 2, '.', ''),
                    'taxable_amount' => number_format($tax['taxable_amount'], 2, '.', ''),
                    'cgst_amount' => number_format($tax['cgst_amount'], 2, '.', ''),
                    'sgst_amount' => number_format($tax['sgst_amount'], 2, '.', ''),
                    'igst_amount' => number_format($tax['igst_amount'], 2, '.', ''),
                    'tax_type' => $tax['tax_type'],
                    'hsn_code' => $product->hsn_code,
                ]);

                // Persist the discounted line values directly as well, bypassing any
                // OrderItem mass-assignment restrictions or model mutators that could
                // otherwise leave the original gross total in the database.
                DB::table('order_items')
                    ->where('id', $orderItemRecord->id)
                    ->update([
                        'total' => $this->formatCents($tax['line_total_cents']),
                        'discount_amount' => $this->formatCents($tax['discount_cents'] ?? 0),
                        'taxable_amount' => number_format((float) $tax['taxable_amount'], 2, '.', ''),
                        'gst_rate' => $tax['gst_rate'],
                        'gst_amount' => number_format((float) $tax['gst_amount'], 2, '.', ''),
                        'cgst_amount' => number_format((float) $tax['cgst_amount'], 2, '.', ''),
                        'sgst_amount' => number_format((float) $tax['sgst_amount'], 2, '.', ''),
                        'igst_amount' => number_format((float) $tax['igst_amount'], 2, '.', ''),
                        'tax_type' => $tax['tax_type'],
                        'updated_at' => now(),
                    ]);

                $orderItemInvoiceDetails[$orderItemRecord->id] = [
                    'cart_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'unit_price_including_gst' => $this->formatCents($this->moneyToCents($item->price_at_time)),
                    'quantity' => (int) $item->quantity,
                    'original_line_amount' => $tax['original_line_amount'],
                    'original_taxable_amount' => $tax['original_taxable_amount'],
                    'discount_amount' => $tax['discount_amount'],
                    'net_taxable_amount' => $tax['taxable_amount'],
                    'taxable_amount' => $tax['taxable_amount'],
                    'gst_rate' => $tax['gst_rate'],
                    'gst_amount' => $tax['gst_amount'],
                    'cgst_amount' => $tax['cgst_amount'],
                    'sgst_amount' => $tax['sgst_amount'],
                    'igst_amount' => $tax['igst_amount'],
                    'amount' => $tax['amount'],
                    'tax_type' => $tax['tax_type'],
                ];
            }
            $priceBreakdown = $order->price_breakdown;
            if (is_string($priceBreakdown)) {
                $priceBreakdown = json_decode($priceBreakdown, true) ?: [];
            }
            $priceBreakdown = is_array($priceBreakdown) ? $priceBreakdown : [];
            $priceBreakdown['item_tax_details'] = $orderItemInvoiceDetails;
            $order->update(['price_breakdown' => $priceBreakdown]);
            $order->update([
                'total_weight' => $totalWeight,
                'box_length' => $maxLength,
                'box_breadth' => $maxBreadth,
                'box_height' => $totalHeight,
            ]);
            CartItem::where('cart_id', $cart->id)->delete();
            DB::commit();
            $order->refresh()->load(['items', 'payment', 'user']);
            $order->refresh();
            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully',
                'order' => [
                    'order_id' => $order->id,
                    'invoice_number' => $order->invoice_number,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'pricing' => [
                        'subtotal' => number_format((float) $subtotal, 2, '.', ''),
                        'discount' => number_format((float) $discount, 2, '.', ''),
                        'taxable_amount' => number_format($taxableAmount, 2, '.', ''),
                        'gst_rate' => $gstRate,
                        'tax_type' => $taxType,
                        'cgst_amount' => number_format($cgstAmount, 2, '.', ''),
                        'sgst_amount' => number_format($sgstAmount, 2, '.', ''),
                        'igst_amount' => number_format($igstAmount, 2, '.', ''),
                        'wallet_used' => number_format($walletUsed, 2, '.', ''),
                        'delivery_charge' => number_format($deliveryCharge, 2, '.', ''),
                        'paid_online' => number_format($finalAmount, 2, '.', ''),
                        'paid_total' => number_format($totalOrderAmount, 2, '.', ''),
                        'total_gst_amount' => number_format($totalGstAmount, 2, '.', ''),
                        'final_amount' => number_format($totalOrderAmount, 2, '.', '')
                    ],
                    'payment' => $payment ? [
                        'transaction_id' => $payment->transaction_id,
                        'payment_gateway' => $payment->payment_gateway,
                        'payment_mode' => $payment->payment_mode,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->payment_status,
                    ] : [
                        'transaction_id' => $walletTransaction
                            ? 'WALLET-TXN-' . $walletTransaction->id
                            : null,
                        'payment_gateway' => 'wallet',
                        'payment_mode' => 'wallet_only',
                        'amount' => $walletUsed,
                        'currency' => 'INR',
                        'status' => 'success',
                    ],
                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'name' => $item->product_name,
                            'image' => $item->product_image,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->total,
                            'discount_amount' => $item->discount_amount ?? '0.00',
                            'hsn_code' => $item->hsn_code,
                            'gst_rate' => $item->gst_rate,
                            'gst_amount' => $item->gst_amount,
                            'taxable_amount' => $item->taxable_amount,
                            'cgst_amount' => $item->cgst_amount,
                            'sgst_amount' => $item->sgst_amount,
                            'igst_amount' => $item->igst_amount,
                            'tax_type' => $item->tax_type,
                        ];
                    }),
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('STORE PAYMENT ERROR', [
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'status' => false,
                'message' => $this->isTest ? $e->getMessage() : 'Payment failed'
            ], 422);
        }
    }
    public function calculateSummary(Request $request)
    {
        try {
            $user = $request->user();
            $request->validate([
                'address_id' => 'nullable|exists:alternative_addresses,id',
                'coupon_code' => 'nullable|string',
                'wallet_amount' => 'nullable|numeric|min:0',
            ]);

            $cart = Cart::where('user_id', $user->id)->firstOrFail();
            $items = CartItem::where('cart_id', $cart->id)->get();
            if ($items->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'Cart empty'], 422);
            }

            $validatedCart = $this->validateCartItems($items);
            $products = $validatedCart['products'];
            $subtotalCents = $this->moneyToCents($validatedCart['subtotal']);
            $subtotal = $subtotalCents / 100;
            $originalPricing = $this->calculateProductTaxDetails($items, $products, 0, $subtotalCents, 'igst');
            $originalTaxableCents = $originalPricing['original_taxable_cents'];

            $coupon = null;
            $discountCents = 0;
            if ($request->filled('coupon_code')) {
                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('status', 1)
                    ->whereDate('expiry_date', '>=', now())
                    ->first();
                if (!$coupon) {
                    return response()->json(['status' => false, 'message' => 'Invalid coupon'], 422);
                }
                if (!in_array($coupon->payment_type, ['razorpay', 'both'], true)) {
                    return response()->json(['status' => false, 'message' => 'This coupon is not applicable for Razorpay orders'], 422);
                }
                $minimumCents = $this->moneyToCents($coupon->min_amount ?? 0);
                if ($minimumCents > 0 && $subtotalCents < $minimumCents) {
                    return response()->json(['status' => false, 'message' => 'Coupon minimum amount not met'], 422);
                }
                if ($coupon->discount_type === 'flat') {
                    $discountCents = $this->moneyToCents($coupon->discount_value ?? 0);
                } else {
                    $discountCents = (int) round(
                        ($originalTaxableCents * $this->normalizeRate($coupon->discount_value ?? 0)) / 100,
                        0,
                        PHP_ROUND_HALF_UP
                    );
                    $maxDiscountCents = $this->moneyToCents($coupon->max_discount ?? 0);
                    if ($maxDiscountCents > 0) {
                        $discountCents = min($discountCents, $maxDiscountCents);
                    }
                }
                $discountCents = max(0, min($discountCents, $originalTaxableCents));
            }

            $taxType = 'igst';
            $address = null;
            if ($request->filled('address_id')) {
                $address = AlternativeAddress::where('id', $request->address_id)
                    ->where('user_id', $user->id)
                    ->first();
                if (!$address) {
                    return response()->json(['status' => false, 'message' => 'Invalid delivery address'], 422);
                }
                $taxType = strtolower(trim((string) $address->state)) === 'delhi' ? 'cgst_sgst' : 'igst';
            }

            $productPricing = $this->calculateProductTaxDetails($items, $products, $discountCents, $subtotalCents, $taxType);
            $discountCents = $productPricing['discount_cents'];
            $deliveryChargeCents = 0;
            if ($address && $address->state) {
                $deliveryRate = DeliveryRate::where('state', $address->state)->where('status', 1)->first();
                if ($deliveryRate) {
                    $deliveryChargeCents = $subtotalCents >= 80000
                        ? 0
                        : $this->moneyToCents($deliveryRate->delivery_charge ?? 0);
                }
            }

            $walletRequestedCents = $this->moneyToCents($request->wallet_amount ?? 0);
            $wallet = StoreWallet::where('user_id', $user->id)->first();
            $walletBalanceCents = $this->moneyToCents($wallet->balance ?? 0);
            if ($walletRequestedCents > $walletBalanceCents) {
                return response()->json(['status' => false, 'message' => 'Insufficient wallet balance'], 422);
            }
            $orderBeforeWalletCents = $productPricing['total_cents'] + $deliveryChargeCents;
            $walletUsedCents = min($walletRequestedCents, $orderBeforeWalletCents);
            $finalAmountCents = max(0, $orderBeforeWalletCents - $walletUsedCents);

            return response()->json([
                'status' => true,
                'breakdown' => [
                    'subtotal' => $this->formatCents($subtotalCents),
                    'original_taxable_amount' => $this->formatCents($productPricing['original_taxable_cents']),
                    'coupon_discount' => $this->formatCents($discountCents),
                    'coupon_discount_taxable_amount' => $this->formatCents($discountCents),
                    'net_taxable_amount' => $this->formatCents($productPricing['taxable_cents']),
                    'product_gst_amount' => $this->formatCents($productPricing['gst_cents']),
                    'product_gst_savings' => $this->formatCents(max(0, $productPricing['original_gst_cents'] - $productPricing['gst_cents'])),
                    'discounted_product_total' => $this->formatCents($productPricing['total_cents']),
                    'delivery_charge' => $this->formatCents($deliveryChargeCents),
                    'wallet_used' => $this->formatCents($walletUsedCents),
                    'final_amount' => $this->formatCents($finalAmountCents),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $this->isTest ? $e->getMessage() : 'Unable to calculate order summary'], 422);
        }
    }
    public function cancelOrder(Request $request, $id)
    {
        $request->validate([
            'cancel_reason' => 'required|array|min:1',
            'cancel_reason.*' => 'string|max:255',
        ]);
        $cancelReason = implode(', ', $request->cancel_reason);
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $order = Order::where('id', $id)
                ->where('user_id', $user->id)
                ->with('items')
                ->firstOrFail();
            if ($order->status == 'cancelled') {
                return response()->json([
                    'status' => false,
                    'message' => 'Order already cancelled'
                ]);
            }
            if (in_array($order->status, ['shipped', 'delivered'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order cannot be cancelled now'
                ]);
            }
            $refundAmount = round((float) $order->total_amount, 2);
            // ðŸ”¥ WALLET
            $wallet = StoreWallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();
            if (!$wallet) {
                $wallet = StoreWallet::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'total_added' => 0,
                    'total_spent' => 0,
                    'total_refunded' => 0
                ]);
            }
            $before = $wallet->balance;
            $after = $before + $refundAmount;
            $wallet->update([
                'balance' => $after,
                'total_refunded' => $wallet->total_refunded + $refundAmount
            ]);
            // ðŸ”¥ WALLET TRANSACTION
            StoreWalletTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'type' => 'credit',
                'amount' => $refundAmount,
                'source' => 'order_cancel',
                'balance_before' => $before,
                'balance_after' => $after,
                'note' => 'Order cancelled refund'
            ]);
            // ðŸ”¥ ITEM-WISE REFUND (PROPORTIONAL)
            $totalOrderAmount = (float) $order->items->sum('total');
            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();
                if ($product) {
                    $newStock = $product->stock_qty + $item->quantity;
                    $status = 'in_stock';
                    if ($newStock == 0) {
                        $status = 'out_of_stock';
                    } elseif ($newStock <= 5) {
                        $status = 'few_left';
                    }
                    $product->update([
                        'stock_qty' => $newStock,
                        'stock_status' => $status
                    ]);
                }
                $itemTotal = $item->total; // âœ… à¤¸à¤¹à¥€ column
                $itemRefund = 0;
                if ($totalOrderAmount > 0) {
                    $itemRefund = ($itemTotal / $totalOrderAmount) * $refundAmount;
                }
                OrderItemCancellation::create([
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'user_id' => $user->id,
                    'quantity' => $item->quantity,
                    'refund_amount' => round($itemRefund, 2),
                    'cancelled_at' => now(),
                    'reason' => $cancelReason
                ]);
            }
            // ðŸ”¥ PAYMENT UPDATE
            if ($order->payment_id) {
                Payment::where('id', $order->payment_id)
                    ->update(['payment_status' => 'refunded']);
            }
            // ðŸ”¥ COMMISSION UPDATE
            EmployeeCommission::where(
                'order_id',
                $order->id
            )->update([
                'status' => 'cancelled'
            ]);
            // ðŸ”¥ ORDER UPDATE
            $order->update([
                'status' => 'cancelled',
                'shipping_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $cancelReason,
            ]);
            DB::commit();
            $order->refresh();
            return response()->json([
                'status' => true,
                'message' => 'Order cancelled & refunded',
                'refund' => [
                    'amount' => $refundAmount,
                    'wallet_before' => $before,
                    'wallet_after' => $after
                ],
                'cancel_reason' => $order->cancel_reason,
                // ðŸ”¥ ADD THIS
                'pricing' => $order->price_breakdown
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function orderDetails($id)
    {
        $user = auth()->user();
        $order = Order::with('items')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        return response()->json([
            'status' => true,
            'data' => [
                'order_id' => $order->id,
                'status' => $order->status,
                'pricing' => $order->price_breakdown,
                'items' => $order->items
            ]
        ]);
    }
    private function validateCartItems($items)
    {
        $productIds = $items->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)
            ->get()
            ->keyBy('id');
        $subtotal = 0.00;
        foreach ($items as $item) {
            $product = $products[$item->product_id] ?? null;
            if (!$product) {
                throw new \Exception('Product not found');
            }
            if ($item->quantity <= 0) {
                throw new \Exception('Invalid quantity');
            }
            if ($product->stock_qty < $item->quantity) {
                throw new \Exception(
                    $product->name . ' only ' . $product->stock_qty . ' left in stock'
                );
            }
            if (
                $item->price_at_time === null ||
                $item->price_at_time <= 0
            ) {
                throw new \Exception(
                    $product->name . ' price not configured'
                );
            }
            $unitPrice = round((float) $item->price_at_time, 2);
            $cartTotal = round((float) $item->total_price, 2);
            $expectedTotal = round(
                $unitPrice * (int) $item->quantity,
                2
            );
            if ($expectedTotal !== $cartTotal) {
                throw new \Exception(
                    $product->name . ' cart amount mismatch'
                );
            }
            $subtotal = round($subtotal + $cartTotal, 2);
        }
        return [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'products' => $products,
        ];
    }
    private function moneyToCents($value): int
    {
        return (int) round(
            ((float) ($value ?? 0)) * 100,
            0,
            PHP_ROUND_HALF_UP
        );
    }
    private function formatCents(int $cents): string
    {
        return number_format(
            $cents / 100,
            2,
            '.',
            ''
        );
    }
    private function calculateInclusiveGst(
        float $amount,
        float $gstRate
    ): float {
        $amountCents = $this->moneyToCents($amount);
        $gst = $this->splitInclusiveGst(
            $amountCents,
            $gstRate
        );
        return $gst['gst_cents'] / 100;
    }
    /**
     * Split a GST-inclusive amount into taxable value and GST. Amounts are paise.
     */
    private function splitInclusiveGst(int $amountCents, float $gstRate): array
    {
        $amountCents = max(0, $amountCents);
        $gstRate = $this->normalizeRate($gstRate);

        if ($amountCents === 0 || $gstRate <= 0) {
            return ['taxable_cents' => $amountCents, 'gst_cents' => 0];
        }

        $gstCents = (int) round(
            ($amountCents * $gstRate) / (100 + $gstRate),
            0,
            PHP_ROUND_HALF_UP
        );

        return [
            'taxable_cents' => $amountCents - $gstCents,
            'gst_cents' => $gstCents,
        ];
    }

    /** Calculate GST on a tax-exclusive base; all money values are paise. */
    private function calculateExclusiveGstCents(int $taxableCents, float $gstRate): int
    {
        if ($taxableCents <= 0 || $gstRate <= 0) {
            return 0;
        }

        return (int) round(
            ($taxableCents * $this->normalizeRate($gstRate)) / 100,
            0,
            PHP_ROUND_HALF_UP
        );
    }

    /**
     * Calculate item-wise GST after allocating coupon discount to the original
     * GST-exclusive taxable base. The cart's unit price and line total include GST.
     */
    private function calculateProductTaxDetails(
        $items,
        $products,
        int $discountCents,
        int $subtotalCents,
        string $taxType
    ): array {
        $baseLines = [];
        $weights = [];
        $originalGrossCents = 0;
        $originalTaxableCents = 0;
        $originalGstCents = 0;
        $hsnCodes = [];

        foreach ($items as $item) {
            $product = $products[$item->product_id] ?? null;
            if (!$product) {
                throw new \Exception('Product not found while calculating GST');
            }

            $grossCents = $this->moneyToCents($item->total_price);
            $unitPriceCents = $this->moneyToCents($item->price_at_time);
            $gstRate = $this->normalizeRate($product->gst_rate ?? 0);
            $originalTax = $this->splitInclusiveGst($grossCents, $gstRate);

            $baseLines[$item->id] = [
                'cart_item_id' => $item->id,
                'product_id' => $item->product_id,
                'unit_price_cents' => $unitPriceCents,
                'quantity' => (int) $item->quantity,
                'gross_cents' => $grossCents,
                'gst_rate' => $gstRate,
                'original_taxable_cents' => $originalTax['taxable_cents'],
                'original_gst_cents' => $originalTax['gst_cents'],
                'hsn_code' => $product->hsn_code,
            ];

            $weights[$item->id] = $originalTax['taxable_cents'];
            $originalGrossCents += $grossCents;
            $originalTaxableCents += $originalTax['taxable_cents'];
            $originalGstCents += $originalTax['gst_cents'];

            if ($product->hsn_code) {
                $hsnCodes[] = $product->hsn_code;
            }
        }

        // A coupon is a discount on taxable value, never more than that value.
        $discountCents = max(0, min($discountCents, $originalTaxableCents));
        $discountMap = $this->allocateDiscountCentsByWeight($discountCents, $weights);

        $gstCents = 0;
        $taxableCents = 0;
        $cgstCents = 0;
        $sgstCents = 0;
        $igstCents = 0;
        $totalCents = 0;
        $itemsTaxDetails = [];

        foreach ($baseLines as $cartItemId => $line) {
            $lineDiscountCents = (int) ($discountMap[$cartItemId] ?? 0);
            $netTaxableCents = max(0, $line['original_taxable_cents'] - $lineDiscountCents);

            // Preserve the source inclusive GST exactly when this line has no discount.
            // For discounted lines, calculate GST from the reduced exclusive taxable base.
            $lineGstCents = $lineDiscountCents === 0
                ? $line['original_gst_cents']
                : $this->calculateExclusiveGstCents($netTaxableCents, $line['gst_rate']);
            $lineTotalCents = $netTaxableCents + $lineGstCents;

            $lineCgstCents = 0;
            $lineSgstCents = 0;
            $lineIgstCents = 0;
            if ($taxType === 'cgst_sgst') {
                $lineCgstCents = intdiv($lineGstCents, 2);
                $lineSgstCents = $lineGstCents - $lineCgstCents;
            } else {
                $lineIgstCents = $lineGstCents;
            }

            $gstCents += $lineGstCents;
            $taxableCents += $netTaxableCents;
            $cgstCents += $lineCgstCents;
            $sgstCents += $lineSgstCents;
            $igstCents += $lineIgstCents;
            $totalCents += $lineTotalCents;

            $itemsTaxDetails[$cartItemId] = [
                'cart_item_id' => $line['cart_item_id'],
                'product_id' => $line['product_id'],
                'unit_price_including_gst' => $this->formatCents($line['unit_price_cents']),
                'quantity' => $line['quantity'],
                'gross_cents' => $line['gross_cents'],
                'original_line_amount' => $this->formatCents($line['gross_cents']),
                'original_taxable_cents' => $line['original_taxable_cents'],
                'original_taxable_amount' => $this->formatCents($line['original_taxable_cents']),
                'original_gst_cents' => $line['original_gst_cents'],
                'original_gst_amount' => $this->formatCents($line['original_gst_cents']),
                'discount_cents' => $lineDiscountCents,
                'discount_amount' => $this->formatCents($lineDiscountCents),
                'taxable_cents' => $netTaxableCents,
                'taxable_amount' => $this->formatCents($netTaxableCents),
                'gst_rate' => $this->formatRate($line['gst_rate']),
                'gst_cents' => $lineGstCents,
                'gst_amount' => $this->formatCents($lineGstCents),
                'cgst_cents' => $lineCgstCents,
                'cgst_amount' => $this->formatCents($lineCgstCents),
                'sgst_cents' => $lineSgstCents,
                'sgst_amount' => $this->formatCents($lineSgstCents),
                'igst_cents' => $lineIgstCents,
                'igst_amount' => $this->formatCents($lineIgstCents),
                'line_total_cents' => $lineTotalCents,
                'amount' => $this->formatCents($lineTotalCents),
                'tax_type' => $taxType,
                'hsn_code' => $line['hsn_code'],
            ];
        }

        return [
            'gst_cents' => $gstCents,
            'taxable_cents' => $taxableCents,
            'cgst_cents' => $cgstCents,
            'sgst_cents' => $sgstCents,
            'igst_cents' => $igstCents,
            'total_cents' => $totalCents,
            'original_gross_cents' => $originalGrossCents,
            'original_taxable_cents' => $originalTaxableCents,
            'original_gst_cents' => $originalGstCents,
            'discount_cents' => $discountCents,
            'hsn_codes' => array_values(array_unique($hsnCodes)),
            'items' => $itemsTaxDetails,
        ];
    }

    /** Allocate discount paise proportionally by original taxable value. */
    private function allocateDiscountCentsByWeight(int $discountCents, array $weights): array
    {
        $weights = array_map(static fn ($value) => max(0, (int) $value), $weights);
        $totalWeight = array_sum($weights);
        $discountCents = max(0, min($discountCents, $totalWeight));
        $allocations = array_fill_keys(array_keys($weights), 0);

        if ($discountCents === 0 || $totalWeight === 0) {
            return $allocations;
        }

        $remainders = [];
        $allocated = 0;
        foreach ($weights as $key => $weight) {
            $numerator = $discountCents * $weight;
            $share = min(intdiv($numerator, $totalWeight), $weight);
            $allocations[$key] = $share;
            $remainders[$key] = $numerator % $totalWeight;
            $allocated += $share;
        }

        $left = $discountCents - $allocated;
        arsort($remainders, SORT_NUMERIC);
        while ($left > 0) {
            $progress = false;
            foreach (array_keys($remainders) as $key) {
                if ($allocations[$key] < $weights[$key]) {
                    $allocations[$key]++;
                    $left--;
                    $progress = true;
                    if ($left === 0) {
                        break;
                    }
                }
            }
            if (!$progress) {
                break;
            }
        }

        return $allocations;
    }
}
