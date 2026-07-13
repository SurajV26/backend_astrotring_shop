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

            $codCharge = config('services.cod_charge');

            $walletUsed = 0;

            $finalAmount =
                $afterDiscount +
                $deliveryCharge +
                $codCharge;

            $sellerState = 'Delhi';

            $productTax = 0;
            $productTaxableAmount = 0;

            $shippingTax = 0;
            $shippingTaxable = 0;

            $codTax = 0;
            $codTaxable = 0;

            $gstRate = 0;

            $hsnCodes = [];

            foreach ($items as $item) {

                $product = $products[$item->product_id];

                $itemTotal = $item->total_price;

                $itemGstRate = $product->gst_rate ?? 0;

                if ($product->hsn_code) {
                    $hsnCodes[] = $product->hsn_code;
                }

                $itemTax = round(
                    ($itemTotal * $itemGstRate) / 100,
                    2
                );

                $itemTaxableAmount = round(
                    $itemTotal - $itemTax,
                    2
                );

                $productTax += $itemTax;
                $productTaxableAmount += $itemTaxableAmount;

                $gstRate = $itemGstRate;
            }

            $hsnCodes = array_unique($hsnCodes);

            $shippingGstRate = 18;

            $shippingTax = round(
                ($deliveryCharge * $shippingGstRate) / 100,
                2
            );

            $shippingTaxable = round(
                $deliveryCharge - $shippingTax,
                2
            );

            $codGstRate = 18;

            $codTax = round(
                ($codCharge * $codGstRate) / 100,
                2
            );

            $codTaxable = round(
                $codCharge - $codTax,
                2
            );

            $taxableAmount =
                $productTaxableAmount +
                $shippingTaxable +
                $codTaxable;

            $hsnCode = implode(',', $hsnCodes);

            $cgstAmount = 0;
            $sgstAmount = 0;
            $igstAmount = 0;

            $taxType = null;

            $productCgstAmount = 0;
            $productSgstAmount = 0;
            $productIgstAmount = 0;

            $shippingCgstAmount = 0;
            $shippingSgstAmount = 0;
            $shippingIgstAmount = 0;

            $codCgstAmount = 0;
            $codSgstAmount = 0;
            $codIgstAmount = 0;

            if (
                strtolower(trim($address->state)) ==
                strtolower(trim($sellerState))
            ) {

                $taxType = 'cgst_sgst';

                $productCgstAmount = round($productTax / 2, 2);
                $productSgstAmount = round($productTax / 2, 2);

                $shippingCgstAmount = round($shippingTax / 2, 2);
                $shippingSgstAmount = round($shippingTax / 2, 2);

                $codCgstAmount = round($codTax / 2, 2);
                $codSgstAmount = round($codTax / 2, 2);

            } else {

                $taxType = 'igst';

                $productIgstAmount = $productTax;

                $shippingIgstAmount = $shippingTax;

                $codIgstAmount = $codTax;
            }

            $cgstAmount =
                $productCgstAmount +
                $shippingCgstAmount +
                $codCgstAmount;

            $sgstAmount =
                $productSgstAmount +
                $shippingSgstAmount +
                $codSgstAmount;

            $igstAmount =
                $productIgstAmount +
                $shippingIgstAmount +
                $codIgstAmount;

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

            $order = Order::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'coupon_id' => $couponId,
                'payment_id' => $payment->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'invoice_sequence' => $nextInvoiceSequence,
                'invoice_number' => 'AT-COD-' . str_pad($nextInvoiceSequence, 4, '0', STR_PAD_LEFT),
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
                    'product_gst_amount' => $productTax,
                    'product_taxable_amount' => $productTaxableAmount,

                    'shipping_gst_rate' => $shippingGstRate,
                    'shipping_gst_amount' => $shippingTax,
                    'shipping_taxable_amount' => $shippingTaxable,

                    'cod_charge' => $codCharge,
                    'cod_gst_rate' => $codGstRate,
                    'cod_gst_amount' => $codTax,
                    'cod_taxable_amount' => $codTaxable,

                    'product_cgst_amount' => $productCgstAmount,
                    'product_sgst_amount' => $productSgstAmount,
                    'product_igst_amount' => $productIgstAmount,

                    'shipping_cgst_amount' => $shippingCgstAmount,
                    'shipping_sgst_amount' => $shippingSgstAmount,
                    'shipping_igst_amount' => $shippingIgstAmount,

                    'cod_cgst_amount' => $codCgstAmount,
                    'cod_sgst_amount' => $codSgstAmount,
                    'cod_igst_amount' => $codIgstAmount,

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
                'email' => $address->email ?? $user->email,
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

            if (
                $couponId &&
                $coupon &&
                $coupon->employee_id &&
                $coupon->employee_id != 1
            ) {

                $percentage = $coupon->employee->commission_percentage ?? 0;

                $commissionAmount =
                    ($order->total_amount * $percentage) / 100;

                EmployeeCommission::create([
                    'employee_id' => $coupon->employee_id,
                    'order_id' => $order->id,
                    'coupon_id' => $coupon->id,
                    'order_amount' => $order->total_amount,
                    'commission_percentage' => $percentage,
                    'commission_amount' => round($commissionAmount, 2),
                    'status' => 'delivery_pending',
                ]);
            }

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
                $itemGstRate = $product->gst_rate ?? 0;

                $itemTax = round(
                    ($item->total_price * $itemGstRate) / 100,
                    2
                );

                $itemTaxableAmount = round(
                    $item->total_price - $itemTax,
                    2
                );

                $itemCgst = 0;
                $itemSgst = 0;
                $itemIgst = 0;

                if ($taxType == 'cgst_sgst') {
                    $itemCgst = round($itemTax / 2, 2);
                    $itemSgst = round($itemTax / 2, 2);
                } else {
                    $itemIgst = $itemTax;
                }
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
                    'gst_rate' => $itemGstRate,
                    'gst_amount' => $itemTax,
                    'taxable_amount' => $itemTaxableAmount,

                    'cgst_amount' => $itemCgst,
                    'sgst_amount' => $itemSgst,
                    'igst_amount' => $itemIgst,

                    'tax_type' => $taxType,
                    'hsn_code' => $product->hsn_code,
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
                    'pricing' => $order->price_breakdown,
                    'items' => $order->items->map(function ($item) {

                        return [

                            'product_id' => $item->product_id,

                            'name' => $item->product_name,

                            'slug' => $item->product_slug,

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

                            'weight' => $item->weight,

                            'length' => $item->length,

                            'breadth' => $item->breadth,

                            'height' => $item->height,
                        ];

                    }),
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

    public function getCodCharge()
    {
        return response()->json([

            'status' => true,

            'cod_charge' => (float) config('services.cod_charge')
        ]);
    }
    
    public function cancelCodOrder($id)
    {
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
                'cancelled_at' => now()
            ]);
    
            DB::commit();
    
            return response()->json([
                'status' => true,
                'message' => 'COD order cancelled successfully',
                'order' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => 'cancelled'
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
}