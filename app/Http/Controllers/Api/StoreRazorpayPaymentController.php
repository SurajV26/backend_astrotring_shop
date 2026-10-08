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

            $walletInput = round((float) ($request->wallet_amount ?? 0), 2);

            // ðŸ”¥ CART
            $cart = Cart::where('user_id', $user->id)->firstOrFail();
            $items = CartItem::where('cart_id', $cart->id)->get();

            if ($items->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'Cart empty']);
            }

            $validatedCart = $this->validateCartItems($items);

            $subtotal = round((float) $validatedCart['subtotal'], 2);

            $discount = 0;
            $couponId = null;

            if ($request->coupon_code) {

                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('status', 1)
                    ->whereDate('expiry_date', '>=', now())
                    ->lockForUpdate()
                    ->first();

                if (!$coupon) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid coupon'
                    ], 422);
                }

                if (
                    $coupon->min_amount &&
                    $subtotal < $coupon->min_amount
                ) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Coupon minimum amount not met'
                    ], 422);
                }

                if ($coupon->discount_type == 'flat') {

                    $discount = round((float) $coupon->discount_value, 2);

                } else {

                    $discount = round(
                        ($subtotal * (float) $coupon->discount_value) / 100,
                        2
                    );

                    if ($coupon->max_discount) {

                        $discount = min(
                            $discount,
                            $coupon->max_discount
                        );
                    }
                }

                // SAFETY
                $discount = round(min($discount, $subtotal), 2);

                $couponId = $coupon->id;
            }

            $afterDiscount = round(max(0, $subtotal - $discount), 2);

            $deliveryCharge = 0;

            if ($request->address_id) {

                $address = AlternativeAddress::where('id', $request->address_id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($address && $address->state) {

                    $deliveryRate = DeliveryRate::where('state', $address->state)
                        ->where('status', 1)
                        ->first();

                    if ($deliveryRate) {
                        $deliveryCharge = $subtotal >= 800
                            ? 0.00
                            : round((float) $deliveryRate->delivery_charge, 2);
                    }
                }
            }

            // ðŸ”¥ WALLET VALIDATION (USER CONTROLLED)
            $wallet = StoreWallet::where('user_id', $user->id)
                ->first();

            if (!$wallet) {

                $wallet = StoreWallet::create([
                    'user_id' => $user->id,
                    'balance' => 0
                ]);
            }

            if ($walletInput > $wallet->balance) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient wallet balance'
                ]);
            }

            if ($walletInput > ($afterDiscount + $deliveryCharge)) {
                $walletInput = ($afterDiscount + $deliveryCharge);
            }

            $walletUsed = round($walletInput, 2);

            $finalAmount = round(max(0, ($afterDiscount + $deliveryCharge) - $walletUsed), 2);

            // FULL WALLET PAYMENT
            if ($finalAmount <= 0) {

                return response()->json([
                    'status' => true,
                    'payment_mode' => 'wallet_only',
                    'order_id' => null,
                    'breakdown' => [
                        'subtotal' => number_format((float) $subtotal, 2, '.', ''),
                        'discount' => number_format((float) $discount, 2, '.', ''),
                        'delivery_charge' => number_format($deliveryCharge, 2, '.', ''),
                        'wallet_used' => number_format($walletUsed, 2, '.', ''),
                        'final_amount' => '0.00'
                    ]
                ]);
            }

            // ðŸ”¥ RAZORPAY
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

            $order = $api->order->create([
                'receipt' => 'store_' . uniqid(),
                'amount' => (int) round($finalAmount * 100),
                'currency' => 'INR',

                'notes' => [
                    'user_id' => $user->id,
                    'address_id' => $request->address_id,
                    'subtotal' => number_format((float) $subtotal, 2, '.', ''),
                    'discount' => number_format((float) $discount, 2, '.', ''),
                    'delivery_charge' => number_format($deliveryCharge, 2, '.', ''),
                    'wallet_used' => number_format($walletUsed, 2, '.', ''),
                    'final_amount' => number_format($finalAmount, 2, '.', '')
                ]
            ]);

            return response()->json([
                'status' => true,
                'order_id' => $order['id'],
                'breakdown' => [
                    'subtotal' => number_format((float) $subtotal, 2, '.', ''),
                    'discount' => number_format((float) $discount, 2, '.', ''),
                    'delivery_charge' => number_format($deliveryCharge, 2, '.', ''),
                    'wallet_used' => number_format($walletUsed, 2, '.', ''),
                    'final_amount' => number_format($finalAmount, 2, '.', '')
                ]
            ]);

        } catch (\Exception $e) {

            Log::error('STORE CREATE ORDER ERROR', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => $this->isTest ? $e->getMessage() : 'Unable to create order'
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
            $walletInput = round((float) ($request->wallet_amount ?? 0), 2);

            // ðŸ”¥ CART
            $cart = Cart::where('user_id', $user->id)->firstOrFail();
            $items = CartItem::where('cart_id', $cart->id)->get();

            if ($items->isEmpty()) {
                throw new \Exception('Cart empty');
            }

            $productIds = $items->pluck('product_id')->unique();

            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;

            foreach ($items as $item) {

                $product = $products[$item->product_id] ?? null;

                if (!$product) {
                    throw new \Exception('Product not found');
                }

                // FINAL STOCK CHECK
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

                $subtotal += $item->total_price;
            }

            // ðŸ”¥ COUPON
            $discount = 0;
            $couponId = null;

            if ($request->coupon_code) {

                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('status', 1)
                    ->whereDate('expiry_date', '>=', now())
                    ->lockForUpdate()
                    ->first();

                if (!$coupon) {
                    throw new \Exception('Invalid coupon');
                }

                if (
                    $coupon->min_amount &&
                    $subtotal < $coupon->min_amount
                ) {

                    throw new \Exception(
                        'Coupon minimum amount not met'
                    );
                }

                if ($coupon->discount_type == 'flat') {

                    $discount = round((float) $coupon->discount_value, 2);

                } else {

                    $discount = round(
                        ($subtotal * (float) $coupon->discount_value) / 100,
                        2
                    );

                    if ($coupon->max_discount) {

                        $discount = min(
                            $discount,
                            $coupon->max_discount
                        );
                    }
                }

                // SAFETY
                $discount = round(min($discount, $subtotal), 2);

                $couponId = $coupon->id;
            }

            $afterDiscount = round(max(0, $subtotal - $discount), 2);

            $deliveryCharge = 0;

            if ($request->address_id) {

                $address = AlternativeAddress::where('id', $request->address_id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($address && $address->state) {

                    $deliveryRate = DeliveryRate::where('state', $address->state)
                        ->where('status', 1)
                        ->first();

                    if ($deliveryRate) {
                        $deliveryCharge = $subtotal >= 800
                            ? 0.00
                            : round((float) $deliveryRate->delivery_charge, 2);
                    }
                }
            }

            $wallet = StoreWallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {

                $wallet = StoreWallet::create([
                    'user_id' => $user->id,
                    'balance' => 0
                ]);
            }

            if ($walletInput > $wallet->balance) {
                throw new \Exception('Invalid wallet usage');
            }

            if ($walletInput > ($afterDiscount + $deliveryCharge)) {
                $walletInput = ($afterDiscount + $deliveryCharge);
            }

            $walletUsed = round($walletInput, 2);
            $finalAmount = round(max(0, ($afterDiscount + $deliveryCharge) - $walletUsed), 2);

            if ($finalAmount > 0 && !$request->razorpay_payment_id) {
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
                    $expectedGatewayAmount = (int) round($finalAmount * 100);

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
                        'discount' => number_format((float) $discount, 2, '.', ''),
                        'wallet_requested' => number_format((float) ($request->wallet_amount ?? 0), 2, '.', ''),
                        'wallet_used' => number_format($walletUsed, 2, '.', ''),
                        'final_amount' => number_format($finalAmount, 2, '.', ''),
                        'delivery_charge' => number_format($deliveryCharge, 2, '.', ''),
                        'coupon_code' => $request->coupon_code
                    ],

                    'payment_response_data' => $paymentData
                ]);
            }

            $address = null;

            if ($request->address_id) {

                $address = DB::table('alternative_addresses')
                    ->where('id', $request->address_id)
                    ->where('user_id', $user->id)
                    ->first();
            }

            $sellerState = 'Delhi';

            $productTax = 0.00;
            $productTaxableAmount = 0.00;

            $shippingTax = 0.00;
            $shippingTaxable = 0.00;

            $hsnCodes = [];
            $itemTaxDetails = [];

            $taxType = (
                $address &&
                strtolower(trim($address->state ?? '')) === strtolower(trim($sellerState))
            ) ? 'cgst_sgst' : 'igst';

            $productCgstAmount = 0.00;
            $productSgstAmount = 0.00;
            $productIgstAmount = 0.00;

            $shippingCgstAmount = 0.00;
            $shippingSgstAmount = 0.00;
            $shippingIgstAmount = 0.00;

            /*
             * PRODUCT GST
             * Product price in store is GST-inclusive.
             * GST = Amount × Rate / (100 + Rate)
             */
            foreach ($items as $item) {

                $product = $products[$item->product_id] ?? null;

                if (!$product) {
                    throw new \Exception('Product not found');
                }

                $itemTotal = round((float) $item->total_price, 2);
                $itemGstRate = round((float) ($product->gst_rate ?? 0), 2);

                if ($product->hsn_code) {
                    $hsnCodes[] = $product->hsn_code;
                }

                $itemTax = $this->calculateInclusiveGst(
                    $itemTotal,
                    $itemGstRate
                );

                $itemTaxableAmount = round(
                    $itemTotal - $itemTax,
                    2
                );

                $itemCgst = 0.00;
                $itemSgst = 0.00;
                $itemIgst = 0.00;

                if ($taxType === 'cgst_sgst') {
                    $itemCgst = round($itemTax / 2, 2);
                    $itemSgst = round($itemTax - $itemCgst, 2);
                } else {
                    $itemIgst = $itemTax;
                }

                $productTax += $itemTax;
                $productTaxableAmount += $itemTaxableAmount;

                $productCgstAmount += $itemCgst;
                $productSgstAmount += $itemSgst;
                $productIgstAmount += $itemIgst;

                $itemTaxDetails[$item->id] = [
                    'gst_rate' => number_format($itemGstRate, 2, '.', ''),
                    'gst_amount' => round($itemTax, 2),
                    'taxable_amount' => round($itemTaxableAmount, 2),
                    'cgst_amount' => round($itemCgst, 2),
                    'sgst_amount' => round($itemSgst, 2),
                    'igst_amount' => round($itemIgst, 2),
                    'tax_type' => $taxType,
                ];
            }

            $hsnCodes = array_unique($hsnCodes);
            $hsnCode = implode(',', $hsnCodes);

            /* SHIPPING GST - delivery charge is GST-inclusive */
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
                $shippingCgstAmount = round($shippingTax / 2, 2);
                $shippingSgstAmount = round(
                    $shippingTax - $shippingCgstAmount,
                    2
                );
            } else {
                $shippingIgstAmount = $shippingTax;
            }

            $taxableAmount = round(
                $productTaxableAmount + $shippingTaxable,
                2
            );

            $cgstAmount = round(
                $productCgstAmount + $shippingCgstAmount,
                2
            );

            $sgstAmount = round(
                $productSgstAmount + $shippingSgstAmount,
                2
            );

            $igstAmount = round(
                $productIgstAmount + $shippingIgstAmount,
                2
            );

            $totalGstAmount = round(
                $cgstAmount + $sgstAmount + $igstAmount,
                2
            );

            /*
             * One order can contain multiple GST rates.
             * orders.gst_rate stores an effective product GST rate.
             */
            $gstRate = $productTaxableAmount > 0
                ? round(
                    ($productTax / $productTaxableAmount) * 100,
                    2
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

            $totalOrderAmount = round(
                $afterDiscount + $deliveryCharge,
                2
            );

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
                    'coupon_discount' => number_format($discount, 2, '.', ''),
                    'delivery_charge' => number_format($deliveryCharge, 2, '.', ''),
                    'wallet_used' => number_format($walletUsed, 2, '.', ''),

                    'product_gst_amount' => number_format($productTax, 2, '.', ''),
                    'product_taxable_amount' => number_format($productTaxableAmount, 2, '.', ''),

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

                OrderItem::create([
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
                    'total' => number_format((float) $item->total_price, 2, '.', ''),
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
            }

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
            ]);

            // CART
            $cart = Cart::where('user_id', $user->id)
                ->firstOrFail();

            $items = CartItem::where('cart_id', $cart->id)
                ->get();

            if ($items->isEmpty()) {

                return response()->json([
                    'status' => false,
                    'message' => 'Cart empty'
                ]);
            }

            $validatedCart = $this->validateCartItems($items);

            $subtotal = round((float) $validatedCart['subtotal'], 2);

            // DELIVERY
            $deliveryCharge = 0;

            if ($request->address_id) {

                $address = AlternativeAddress::where('id', $request->address_id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($address && $address->state) {

                    $deliveryRate = DeliveryRate::where(
                            'state',
                            $address->state
                        )
                        ->where('status', 1)
                        ->first();

                    if ($deliveryRate) {

                        $deliveryCharge =
                            $subtotal >= 800
                                ? 0
                                : (float) $deliveryRate->delivery_charge;
                    }
                }
            }

            return response()->json([

                'status' => true,

                'breakdown' => [

                    'subtotal' => number_format((float) $subtotal, 2, '.', ''),

                    'delivery_charge' => number_format($deliveryCharge, 2, '.', ''),

                    'final_amount' => number_format($subtotal + $deliveryCharge, 2, '.', '')
                ]
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
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

            // $refundAmount = $order->total_amount;
            $refundAmount = $order->subtotal;

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
            // $totalOrderAmount = $order->total_amount;
            $totalOrderAmount = $order->subtotal;

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

    private function calculateInclusiveGst(float $amount, float $gstRate): float
    {
        $amount = round(max(0, $amount), 2);
        $gstRate = round(max(0, $gstRate), 2);

        if ($amount <= 0 || $gstRate <= 0) {
            return 0.00;
        }

        return round(
            ($amount * $gstRate) / (100 + $gstRate),
            2
        );
    }
}