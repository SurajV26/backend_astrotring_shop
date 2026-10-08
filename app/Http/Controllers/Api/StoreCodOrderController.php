<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\AlternativeAddress;
use App\Models\EmployeeCommission;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\DeliveryRate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;

class StoreCodOrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = $request->user();

            $request->validate([
                'coupon_code' => 'nullable|string',
                'address_id' => 'required|exists:alternative_addresses,id',
            ]);

            $address = AlternativeAddress::where('id', $request->address_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$address) {
                throw new \Exception('Invalid delivery address');
            }

            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart) {
                throw new \Exception('Cart not found');
            }

            $items = CartItem::where('cart_id', $cart->id)->get();

            if ($items->isEmpty()) {
                throw new \Exception('Cart empty');
            }

            /*
             * Lock all products before calculating price/stock/tax.
             */
            $productIds = $items->pluck('product_id')->unique();

            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            /*
             * Use integer paise for all money calculations.
             * This avoids floating-point errors such as 99.9999999.
             */
            $subtotalCents = 0;

            foreach ($items as $item) {
                $product = $products[$item->product_id] ?? null;

                if (!$product) {
                    throw new \Exception('Product removed from store');
                }

                if (($product->status ?? 1) != 1) {
                    throw new \Exception($product->name . ' unavailable');
                }

                $quantity = (int) $item->quantity;

                if ($quantity <= 0) {
                    throw new \Exception($product->name . ' has invalid quantity');
                }

                $stockQty = (int) $product->stock_qty;

                if ($stockQty <= 0) {
                    throw new \Exception($product->name . ' out of stock');
                }

                if ($quantity > $stockQty) {
                    throw new \Exception(
                        $product->name . ' only ' . $stockQty . ' left in stock'
                    );
                }

                $priceCents = $this->moneyToCents($item->price_at_time);
                $itemTotalCents = $this->moneyToCents($item->total_price);

                if ($priceCents <= 0) {
                    throw new \Exception($product->name . ' price missing in cart');
                }

                $expectedTotalCents = $priceCents * $quantity;

                if ($expectedTotalCents !== $itemTotalCents) {
                    throw new \Exception($product->name . ' cart amount mismatch');
                }

                $subtotalCents += $itemTotalCents;
            }

            /*
             * Coupon calculation - still based on product subtotal,
             * exactly as the existing business flow does.
             */
            $discountCents = 0;
            $couponId = null;
            $coupon = null;

            if ($request->coupon_code) {
                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('status', 1)
                    ->whereDate('expiry_date', '>=', now())
                    ->lockForUpdate()
                    ->first();

                if (!$coupon) {
                    throw new \Exception('Invalid coupon');
                }

                if (!in_array($coupon->payment_type, ['cod', 'both'], true)) {
                    throw new \Exception(
                        'This coupon is applicable only for COD orders'
                    );
                }

                $couponMinAmountCents = $this->moneyToCents($coupon->min_amount ?? 0);

                if ($couponMinAmountCents > 0 && $subtotalCents < $couponMinAmountCents) {
                    throw new \Exception('Coupon minimum amount not met');
                }

                if ($coupon->discount_type === 'flat') {
                    $discountCents = $this->moneyToCents($coupon->discount_value ?? 0);
                } else {
                    $discountRate = $this->normalizeRate($coupon->discount_value ?? 0);

                    $discountCents = (int) round(
                        ($subtotalCents * $discountRate) / 100,
                        0,
                        PHP_ROUND_HALF_UP
                    );

                    $maxDiscountCents = $this->moneyToCents($coupon->max_discount ?? 0);

                    if ($maxDiscountCents > 0) {
                        $discountCents = min($discountCents, $maxDiscountCents);
                    }
                }

                $discountCents = max(
                    0,
                    min($discountCents, $subtotalCents)
                );

                $couponId = $coupon->id;
            }

            $afterDiscountCents = max(
                0,
                $subtotalCents - $discountCents
            );

            /*
             * Delivery charge
             */
            $deliveryChargeCents = 0;

            $deliveryRate = DeliveryRate::where('state', $address->state)
                ->where('status', 1)
                ->first();

            if ($deliveryRate) {
                $deliveryChargeCents = $subtotalCents >= 80000
                    ? 0
                    : $this->moneyToCents($deliveryRate->delivery_charge ?? 0);
            }

            /*
             * COD charge
             */
            $codChargeCents = $this->moneyToCents(
                config('services.cod_charge', 0)
            );

            $walletUsedCents = 0;

            $finalAmountCents =
                $afterDiscountCents +
                $deliveryChargeCents +
                $codChargeCents;

            /*
             * Tax calculation.
             * Product, delivery and COD prices are GST-inclusive.
             */
            $sellerState = 'Delhi';

            $taxType = strtolower(trim((string) $address->state)) ===
                strtolower(trim($sellerState))
                ? 'cgst_sgst'
                : 'igst';

            $productTaxCents = 0;
            $productTaxableCents = 0;

            $shippingTaxCents = 0;
            $shippingTaxableCents = 0;

            $codTaxCents = 0;
            $codTaxableCents = 0;

            $productCgstCents = 0;
            $productSgstCents = 0;
            $productIgstCents = 0;

            $shippingCgstCents = 0;
            $shippingSgstCents = 0;
            $shippingIgstCents = 0;

            $codCgstCents = 0;
            $codSgstCents = 0;
            $codIgstCents = 0;

            $hsnCodes = [];
            $itemTaxDetails = [];

            /*
             * ITEM-WISE PRODUCT GST
             */
            foreach ($items as $item) {
                $product = $products[$item->product_id] ?? null;

                if (!$product) {
                    throw new \Exception('Product not found');
                }

                $itemTotalCents = $this->moneyToCents($item->total_price);
                $itemGstRate = $this->normalizeRate($product->gst_rate ?? 0);

                if ($product->hsn_code) {
                    $hsnCodes[] = $product->hsn_code;
                }

                $itemTax = $this->splitInclusiveGst(
                    $itemTotalCents,
                    $itemGstRate
                );

                $itemTaxCents = $itemTax['gst_cents'];
                $itemTaxableCents = $itemTax['taxable_cents'];

                $itemCgstCents = 0;
                $itemSgstCents = 0;
                $itemIgstCents = 0;

                if ($taxType === 'cgst_sgst') {
                    $itemCgstCents = intdiv($itemTaxCents, 2);
                    $itemSgstCents = $itemTaxCents - $itemCgstCents;
                } else {
                    $itemIgstCents = $itemTaxCents;
                }

                $productTaxCents += $itemTaxCents;
                $productTaxableCents += $itemTaxableCents;

                $productCgstCents += $itemCgstCents;
                $productSgstCents += $itemSgstCents;
                $productIgstCents += $itemIgstCents;

                /*
                 * Save the exact same calculated values that will be used
                 * in order_items. No second GST calculation later.
                 */
                $itemTaxDetails[$item->id] = [
                    'gst_rate' => $itemGstRate,
                    'gst_cents' => $itemTaxCents,
                    'taxable_cents' => $itemTaxableCents,
                    'cgst_cents' => $itemCgstCents,
                    'sgst_cents' => $itemSgstCents,
                    'igst_cents' => $itemIgstCents,
                    'tax_type' => $taxType,
                ];
            }

            $hsnCodes = array_values(array_unique($hsnCodes));
            $hsnCode = implode(',', $hsnCodes);

            /*
             * DELIVERY GST - 18%
             */
            $shippingGstRate = $this->normalizeRate(18);

            $shippingTax = $this->splitInclusiveGst(
                $deliveryChargeCents,
                $shippingGstRate
            );

            $shippingTaxCents = $shippingTax['gst_cents'];
            $shippingTaxableCents = $shippingTax['taxable_cents'];

            if ($taxType === 'cgst_sgst') {
                $shippingCgstCents = intdiv($shippingTaxCents, 2);
                $shippingSgstCents = $shippingTaxCents - $shippingCgstCents;
            } else {
                $shippingIgstCents = $shippingTaxCents;
            }

            /*
             * COD GST - 18%
             */
            $codGstRate = $this->normalizeRate(18);

            $codTax = $this->splitInclusiveGst(
                $codChargeCents,
                $codGstRate
            );

            $codTaxCents = $codTax['gst_cents'];
            $codTaxableCents = $codTax['taxable_cents'];

            if ($taxType === 'cgst_sgst') {
                $codCgstCents = intdiv($codTaxCents, 2);
                $codSgstCents = $codTaxCents - $codCgstCents;
            } else {
                $codIgstCents = $codTaxCents;
            }

            /*
             * ORDER-LEVEL GST SUMMARY
             */
            $taxableAmountCents =
                $productTaxableCents +
                $shippingTaxableCents +
                $codTaxableCents;

            $cgstAmountCents =
                $productCgstCents +
                $shippingCgstCents +
                $codCgstCents;

            $sgstAmountCents =
                $productSgstCents +
                $shippingSgstCents +
                $codSgstCents;

            $igstAmountCents =
                $productIgstCents +
                $shippingIgstCents +
                $codIgstCents;

            /*
             * For multiple products with different GST rates, this is the
             * effective product GST rate. Single-rate orders stay exact.
             */
            $gstRate = $productTaxableCents > 0
                ? round(
                    ($productTaxCents / $productTaxableCents) * 100,
                    2,
                    PHP_ROUND_HALF_UP
                )
                : 0.00;

            /*
             * Payment record
             */
            $finalAmount = $this->formatCents($finalAmountCents);

            $payment = Payment::create([
                'user_id' => $user->id,
                'platform' => 'astrotring_store',
                'order_id' => null,
                'payment_gateway' => 'cod',
                'transaction_id' => 'COD-' . strtoupper(uniqid()),
                'amount' => $finalAmount,
                'currency' => 'INR',
                'payment_status' => 'success',
                'payment_mode' => 'cod',
                'customer_email' => $user->email,
                'customer_phone' => trim(
                    ($user->country_code ?? '') .
                    ($user->mobile ?? '')
                ),
                'payment_request_data' => [
                    'subtotal' => $this->formatCents($subtotalCents),
                    'discount' => $this->formatCents($discountCents),
                    'delivery_charge' => $this->formatCents($deliveryChargeCents),
                    'cod_charge' => $this->formatCents($codChargeCents),
                    'final_amount' => $finalAmount,
                ],
                'payment_response_data' => [
                    'type' => 'cash_on_delivery',
                ],
            ]);

            /*
             * Invoice number
             */
            $start = (int) env('INVOICE_START_NUMBER', 1);

            $usedNumbers = Order::whereNotNull('invoice_sequence')
                ->orderBy('invoice_sequence')
                ->pluck('invoice_sequence')
                ->toArray();

            $nextInvoiceSequence = $start;

            foreach ($usedNumbers as $number) {
                if ((int) $number === $nextInvoiceSequence) {
                    $nextInvoiceSequence++;
                } elseif ((int) $number > $nextInvoiceSequence) {
                    break;
                }
            }

            /*
             * ORDER MASTER RECORD
             */
            $order = Order::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'coupon_id' => $couponId,
                'payment_id' => $payment->id,

                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'invoice_sequence' => $nextInvoiceSequence,
                'invoice_number' => 'AT-COD-' . str_pad(
                    $nextInvoiceSequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

                'hsn_code' => $hsnCode,

                'subtotal' => $this->formatCents($subtotalCents),
                'discount' => $this->formatCents($discountCents),
                'delivery_charge' => $this->formatCents($deliveryChargeCents),

                'wallet_used' => $this->formatCents($walletUsedCents),
                'advance_paid_amount' => '0.00',
                'remaining_cod_amount' => $finalAmount,
                'is_cod_advance' => false,
                'paid_amount' => '0.00',

                'total_amount' => $finalAmount,

                'price_breakdown' => [
                    'subtotal' => $this->formatCents($subtotalCents),
                    'coupon_discount' => $this->formatCents($discountCents),
                    'delivery_charge' => $this->formatCents($deliveryChargeCents),
                    'cod_charge' => $this->formatCents($codChargeCents),

                    'product_taxable_amount' => $this->formatCents($productTaxableCents),
                    'product_gst_amount' => $this->formatCents($productTaxCents),

                    'shipping_gst_rate' => $this->formatRate($shippingGstRate),
                    'shipping_taxable_amount' => $this->formatCents($shippingTaxableCents),
                    'shipping_gst_amount' => $this->formatCents($shippingTaxCents),

                    'cod_gst_rate' => $this->formatRate($codGstRate),
                    'cod_taxable_amount' => $this->formatCents($codTaxableCents),
                    'cod_gst_amount' => $this->formatCents($codTaxCents),

                    'product_cgst_amount' => $this->formatCents($productCgstCents),
                    'product_sgst_amount' => $this->formatCents($productSgstCents),
                    'product_igst_amount' => $this->formatCents($productIgstCents),

                    'shipping_cgst_amount' => $this->formatCents($shippingCgstCents),
                    'shipping_sgst_amount' => $this->formatCents($shippingSgstCents),
                    'shipping_igst_amount' => $this->formatCents($shippingIgstCents),

                    'cod_cgst_amount' => $this->formatCents($codCgstCents),
                    'cod_sgst_amount' => $this->formatCents($codSgstCents),
                    'cod_igst_amount' => $this->formatCents($codIgstCents),

                    'taxable_amount' => $this->formatCents($taxableAmountCents),
                    'gst_rate' => $this->formatRate($gstRate),
                    'tax_type' => $taxType,

                    'cgst_amount' => $this->formatCents($cgstAmountCents),
                    'sgst_amount' => $this->formatCents($sgstAmountCents),
                    'igst_amount' => $this->formatCents($igstAmountCents),
                    'total_gst_amount' => $this->formatCents(
                        $cgstAmountCents + $sgstAmountCents + $igstAmountCents
                    ),

                    'wallet_used' => $this->formatCents($walletUsedCents),
                    'advance_paid_amount' => '0.00',
                    'remaining_cod_amount' => $finalAmount,
                    'paid_amount' => '0.00',
                    'final_amount' => $finalAmount,
                ],

                'address_id' => $address->id,
                'name' => $address->name,
                'email' => $address->email ?? $user->email,
                'mobile' => $address->mobile,
                'alternative_mobile' => $address->alternative_mobile,
                'city' => $address->city,
                'state_code' => $address->state_code,
                'state' => $address->state,
                'country' => $address->country ?? 'India',
                'address' => $address->address,
                'pincode' => $address->pincode,

                'taxable_amount' => $this->formatCents($taxableAmountCents),
                'gst_rate' => $this->formatRate($gstRate),
                'cgst_amount' => $this->formatCents($cgstAmountCents),
                'sgst_amount' => $this->formatCents($sgstAmountCents),
                'igst_amount' => $this->formatCents($igstAmountCents),
                'tax_type' => $taxType,

                'status' => 'pending',
                'shipping_status' => 'pending',
                'paid_at' => null,
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

                $percentage = $this->normalizeRate(
                    $employee?->commission_percentage ?? 0
                );

                $orderAmountCents = $this->moneyToCents(
                    $order->total_amount
                );

                $commissionCents = (int) round(
                    ($orderAmountCents * $percentage) / 100,
                    0,
                    PHP_ROUND_HALF_UP
                );

                EmployeeCommission::create([
                    'employee_id' => $coupon->employee_id,
                    'order_id' => $order->id,
                    'coupon_id' => $coupon->id,
                    'order_amount' => $order->total_amount,
                    'commission_percentage' => $this->formatRate($percentage),
                    'commission_amount' => $this->formatCents($commissionCents),
                    'status' => 'delivery_pending',
                ]);
            }

            $payment->update([
                'order_id' => $order->id,
            ]);

            /*
             * STOCK UPDATE
             */
            foreach ($items as $item) {
                $product = $products[$item->product_id];

                $newStock = (int) $product->stock_qty - (int) $item->quantity;
                $stockStatus = 'in_stock';

                if ($newStock <= 0) {
                    $stockStatus = 'out_of_stock';
                } elseif ($newStock <= 5) {
                    $stockStatus = 'few_left';
                }

                $product->update([
                    'stock_qty' => $newStock,
                    'stock_status' => $stockStatus,
                ]);
            }

            /*
             * PACKAGE DIMENSIONS + ORDER ITEMS
             * GST values come from $itemTaxDetails so the same calculation
             * is used in both order_items and orders.
             */
            $totalWeight = 0.00;
            $maxLength = 0.00;
            $maxBreadth = 0.00;
            $totalHeight = 0.00;

            foreach ($items as $item) {
                $product = $products[$item->product_id];

                $productWeight = $this->decimal2($product->weight ?? 0);
                $productLength = $this->decimal2($product->length ?? 0);
                $productBreadth = $this->decimal2($product->breadth ?? 0);
                $productHeight = $this->decimal2($product->height ?? 0);

                $totalWeight = $this->decimal2(
                    $totalWeight + ($productWeight * (int) $item->quantity)
                );

                $maxLength = max($maxLength, $productLength);
                $maxBreadth = max($maxBreadth, $productBreadth);

                $totalHeight = $this->decimal2(
                    $totalHeight + ($productHeight * (int) $item->quantity)
                );

                $tax = $itemTaxDetails[$item->id] ?? null;

                if (!$tax) {
                    throw new \Exception(
                        'GST calculation missing for order item ID ' . $item->id
                    );
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,

                    'product_name' => $product->name ?? '',
                    'product_slug' => $product->slug ?? '',
                    'product_image' => $product->image ?? '',

                    'ratti' => $item->ratti !== null
                        ? $this->formatRate($item->ratti)
                        : null,

                    'quantity' => (int) $item->quantity,

                    'price' => $this->formatCents(
                        $this->moneyToCents($item->price_at_time)
                    ),

                    'total' => $this->formatCents(
                        $this->moneyToCents($item->total_price)
                    ),

                    'weight' => $this->formatDecimal($productWeight),
                    'length' => $this->formatDecimal($productLength),
                    'breadth' => $this->formatDecimal($productBreadth),
                    'height' => $this->formatDecimal($productHeight),

                    'gst_rate' => $this->formatRate($tax['gst_rate']),
                    'gst_amount' => $this->formatCents($tax['gst_cents']),
                    'taxable_amount' => $this->formatCents($tax['taxable_cents']),

                    'cgst_amount' => $this->formatCents($tax['cgst_cents']),
                    'sgst_amount' => $this->formatCents($tax['sgst_cents']),
                    'igst_amount' => $this->formatCents($tax['igst_cents']),

                    'tax_type' => $tax['tax_type'],
                    'hsn_code' => $product->hsn_code,
                ]);
            }

            $order->update([
                'total_weight' => $this->formatDecimal($totalWeight),
                'box_length' => $this->formatDecimal($maxLength),
                'box_breadth' => $this->formatDecimal($maxBreadth),
                'box_height' => $this->formatDecimal($totalHeight),
            ]);

            CartItem::where('cart_id', $cart->id)->delete();

            DB::commit();

            $order->refresh()->load([
                'items',
                'payment',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'COD order placed successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice_number' => $order->invoice_number,
                    'status' => $order->status,
                    'payment_status' => $payment->payment_status,
                    'payment_mode' => $payment->payment_mode,
                    'pricing' => $order->price_breakdown,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'name' => $item->product_name,
                            'slug' => $item->product_slug,
                            'image' => $item->product_image,
                            'quantity' => (int) $item->quantity,
                            'price' => $this->formatCents(
                                $this->moneyToCents($item->price)
                            ),
                            'total' => $this->formatCents(
                                $this->moneyToCents($item->total)
                            ),
                            'hsn_code' => $item->hsn_code,
                            'gst_rate' => $this->formatRate($item->gst_rate),
                            'gst_amount' => $this->formatCents(
                                $this->moneyToCents($item->gst_amount)
                            ),
                            'taxable_amount' => $this->formatCents(
                                $this->moneyToCents($item->taxable_amount)
                            ),
                            'cgst_amount' => $this->formatCents(
                                $this->moneyToCents($item->cgst_amount)
                            ),
                            'sgst_amount' => $this->formatCents(
                                $this->moneyToCents($item->sgst_amount)
                            ),
                            'igst_amount' => $this->formatCents(
                                $this->moneyToCents($item->igst_amount)
                            ),
                            'tax_type' => $item->tax_type,
                            'weight' => $this->formatDecimal($item->weight),
                            'length' => $this->formatDecimal($item->length),
                            'breadth' => $this->formatDecimal($item->breadth),
                            'height' => $this->formatDecimal($item->height),
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('COD ORDER ERROR', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getCodCharge()
    {
        return response()->json([

            'status' => true,

            'cod_charge' => (float) config('services.cod_charge')
        ]);
    }
    
    public function cancelCodOrder(Request $request, $id)
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
    
            // STOCK WAPAS ADD
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
            }
    
            // PAYMENT STATUS UPDATE
            if ($order->payment_id) {
                $updated = Payment::where('id', $order->payment_id)
                    ->update(['payment_status' => 'cancelled']);
    
                if (!$updated) {
                    throw new \Exception('Payment status update failed');
                }
            }

            // ðŸ”¥ COMMISSION UPDATE
            EmployeeCommission::where(
                'order_id',
                $order->id
            )->update([
                'status' => 'cancelled'
            ]);
    
            // ORDER STATUS UPDATE
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
                'message' => 'COD order cancelled successfully',
                'order' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => 'cancelled',
                    'cancel_reason'  => $order->cancel_reason,
                ]
            ]);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
    
            Log::error('COD CANCEL ERROR', [
                'message' => $e->getMessage()
            ]);
    
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }


    /**
     * Normalize a numeric value to exactly 2 decimal places for monetary-like values.
     */
    private function decimal2($value): float
    {
        return round((float) ($value ?? 0), 2, PHP_ROUND_HALF_UP);
    }

    /**
     * Normalize GST/percentage rates to exactly 2 decimals.
     */
    private function normalizeRate($value): float
    {
        return round((float) ($value ?? 0), 2, PHP_ROUND_HALF_UP);
    }

    /**
     * Convert a monetary value to integer paise before calculations.
     */
    private function moneyToCents($value): int
    {
        return (int) round(
            ((float) ($value ?? 0)) * 100,
            0,
            PHP_ROUND_HALF_UP
        );
    }

    /**
     * Format integer paise as exactly 0.00.
     */
    private function formatCents(int $cents): string
    {
        return number_format(
            $cents / 100,
            2,
            '.',
            ''
        );
    }

    /**
     * Format a percentage/rate as exactly 0.00.
     */
    private function formatRate($rate): string
    {
        return number_format(
            $this->normalizeRate($rate),
            2,
            '.',
            ''
        );
    }

    /**
     * Format non-currency decimal fields (weight/dimensions/ratti) as 0.00.
     */
    private function formatDecimal($value): string
    {
        return number_format(
            $this->decimal2($value),
            2,
            '.',
            ''
        );
    }

    /**
     * Extract GST from a GST-inclusive amount.
     *
     * Example: 599.00 at 18% => GST 91.37, taxable 507.63.
     */
    private function splitInclusiveGst(int $amountCents, float $gstRate): array
    {
        $amountCents = max(0, $amountCents);
        $gstRate = $this->normalizeRate($gstRate);

        if ($amountCents === 0 || $gstRate <= 0) {
            return [
                'taxable_cents' => $amountCents,
                'gst_cents' => 0,
            ];
        }

        $gstCents = (int) round(
            ($amountCents * $gstRate) / (100 + $gstRate),
            0,
            PHP_ROUND_HALF_UP
        );

        $taxableCents = $amountCents - $gstCents;

        return [
            'taxable_cents' => $taxableCents,
            'gst_cents' => $gstCents,
        ];
    }
}