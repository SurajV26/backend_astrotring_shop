<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\AlternativeAddress;
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

            $items = CartItem::where('cart_id', $cart->id)
                ->get();

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
                    throw new \Exception(
                        'Product removed from store'
                    );
                }

                if (($product->status ?? 1) != 1) {
                    throw new \Exception(
                        $product->name . ' unavailable'
                    );
                }

                if ($product->stock_qty <= 0) {

                    throw new \Exception(
                        $product->name . ' out of stock'
                    );
                }

                if ($item->quantity > $product->stock_qty) {

                    throw new \Exception(
                        $product->name .
                        ' only ' .
                        $product->stock_qty .
                        ' left in stock'
                    );
                }

                if (
                    $item->price_at_time === null ||
                    $item->price_at_time <= 0
                ) {
                    throw new \Exception(
                        $product->name .
                        ' price missing in cart'
                    );
                }

                $expectedTotal =
                    $item->price_at_time * $item->quantity;

                if (
                    (float)$expectedTotal !=
                    (float)$item->total_price
                ) {

                    throw new \Exception(
                        $product->name .
                        ' cart amount mismatch'
                    );
                }

                $subtotal += $item->total_price;
            }

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

                    $discount = $coupon->discount_value;

                } else {

                    $discount =
                        ($subtotal * $coupon->discount_value) / 100;

                    if ($coupon->max_discount) {

                        $discount = min(
                            $discount,
                            $coupon->max_discount
                        );
                    }
                }

                $couponId = $coupon->id;
            }

            $afterDiscount = max(0, $subtotal - $discount);

            $deliveryCharge = 0;

            $deliveryRate = DeliveryRate::where('state', $address->state)
                ->where('status', 1)
                ->first();

            if ($deliveryRate) {
                $deliveryCharge = $subtotal >= 800 ? 0 : (float) $deliveryRate->delivery_charge;
            }

            $codCharge = env('COD_CHARGE', 499);

            $walletUsed = 0;

            $finalAmount =
                $afterDiscount +
                $deliveryCharge +
                $codCharge;

            $sellerState = 'Delhi';

            $totalTax = 0;
            $taxableAmount = 0;
            $gstRate = 0;

            $hsnCodes = [];

            foreach ($items as $item) {

                $product = $products[$item->product_id];

                $itemTotal = $item->total_price;

                $itemGstRate = $product->gst_rate ?? 0;

                if ($product->hsn_code) {
                    $hsnCodes[] = $product->hsn_code;
                }

                $itemTaxableAmount = round(
                    ($itemTotal * 100) / (100 + $itemGstRate),
                    2
                );

                $itemTax = round(
                    $itemTotal - $itemTaxableAmount,
                    2
                );

                $taxableAmount += $itemTaxableAmount;

                $totalTax += $itemTax;

                $gstRate = $itemGstRate;
            }

            $hsnCodes = array_unique($hsnCodes);

            $hsnCode = implode(',', $hsnCodes);

            $cgstAmount = 0;
            $sgstAmount = 0;
            $igstAmount = 0;

            $taxType = null;

            if (
                strtolower(trim($address->state))
                ==
                strtolower(trim($sellerState))
            ) {

                $taxType = 'cgst_sgst';

                $cgstAmount = round($totalTax / 2, 2);
                $sgstAmount = round($totalTax / 2, 2);

            } else {

                $taxType = 'igst';

                $igstAmount = $totalTax;
            }

            $payment = Payment::create([
                'user_id' => $user->id,
                'platform' => 'astrotring_store',
                'order_id' => null,
                'payment_gateway' => 'cod',
                'transaction_id' => 'COD-' . strtoupper(uniqid()),
                'amount' => $finalAmount,
                'currency' => 'INR',
                'payment_status' => 'paid',
                'payment_mode' => 'cod',
                'customer_email' => $user->email,
                'customer_phone' =>
                    trim(
                        ($user->country_code ?? '') .
                        ($user->mobile ?? '')
                    ),
                'payment_request_data' => [
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'delivery_charge' => $deliveryCharge,
                    'cod_charge' => $codCharge,
                    'final_amount' => $finalAmount,
                ],
                'payment_response_data' => [
                    'type' => 'cash_on_delivery'
                ]
            ]);

            $order = Order::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'coupon_id' => $couponId,
                'payment_id' => $payment->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'invoice_number' => null,
                'hsn_code' => $hsnCode,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'wallet_used' => 0,
                'delivery_charge' => $deliveryCharge,
                'paid_amount' => 0,
                'total_amount' => $finalAmount,
                'price_breakdown' => [
                    'subtotal' => $subtotal,
                    'coupon_discount' => $discount,
                    'delivery_charge' => $deliveryCharge,
                    'cod_charge' => $codCharge,
                    'taxable_amount' => $taxableAmount,
                    'gst_rate' => $gstRate,
                    'tax_type' => $taxType,
                    'cgst_amount' => $cgstAmount,
                    'sgst_amount' => $sgstAmount,
                    'igst_amount' => $igstAmount,
                    'final_amount' => $finalAmount,
                ],
                'address_id' => $address->id,
                'name' => $address->name,
                'email' => $user->email,
                'mobile' => $address->mobile,
                'alternative_mobile' => $address->alternative_mobile,
                'city' => $address->city,
                'state_code' => $address->state_code,
                'state' => $address->state,
                'address' => $address->address,
                'pincode' => $address->pincode,
                'taxable_amount' => $taxableAmount,
                'gst_rate' => $gstRate,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'igst_amount' => $igstAmount,
                'tax_type' => $taxType,
                'status' => 'pending',
                'shipping_status' => 'pending',
                'paid_at' => null,
            ]);

            $order->invoice_number = 'AT-COD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);

            $order->save();

            $payment->update([
                'order_id' => $order->id
            ]);

            foreach ($items as $item) {
                $product = $products[$item->product_id];
                $newStock = $product->stock_qty - $item->quantity;
                $stockStatus = 'in_stock';

                if ($newStock <= 0) {

                    $stockStatus = 'out_of_stock';

                } elseif ($newStock <= 5) {

                    $stockStatus = 'few_left';
                }

                $product->update([
                    'stock_qty' => $newStock,
                    'stock_status' => $stockStatus
                ]);
            }

            $totalWeight = 0;
            $maxLength = 0;
            $maxBreadth = 0;
            $totalHeight = 0;

            foreach ($items as $item) {
                $product = $products[$item->product_id];
                $totalWeight += (($product->weight ?? 0) * $item->quantity);
                $maxLength = max($maxLength, $product->length ?? 0);
                $maxBreadth = max($maxBreadth, $product->breadth ?? 0);
                $totalHeight += (($product->height ?? 0) * $item->quantity);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $product->name ?? '',
                    'product_slug' => $product->slug ?? '',
                    'product_image' => $product->image ?? '',
                    'ratti' => $item->ratti,
                    'quantity' => $item->quantity,
                    'price' => $item->price_at_time,
                    'total' => $item->total_price,
                    'weight' => $product->weight,
                    'length' => $product->length,
                    'breadth' => $product->breadth,
                    'height' => $product->height,
                ]);
            }

            $order->update([
                'total_weight' => $totalWeight,
                'box_length' => $maxLength,
                'box_breadth' => $maxBreadth,
                'box_height' => $totalHeight,
            ]);

            CartItem::where('cart_id', $cart->id)
                ->delete();

            DB::commit();

            $order->refresh()->load([
                'items',
                'payment'
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
                    'pricing' => [
                        'subtotal' => $subtotal,
                        'discount' => $discount,
                        'delivery_charge' => $deliveryCharge,
                        'cod_charge' => $codCharge,
                        'final_amount' => $finalAmount,
                    ],
                    'items' => $order->items
                ]
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
                'message' => $e->getMessage()
            ], 422);
        }
    }
}