<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DB;

class OrderApiController extends Controller
{
    // ✅ PLACE ORDER
    // not in use yet, will be used in future when we implement checkout flow
    public function place(Request $request)
    {
        $request->validate([
            'coupon_code' => 'nullable|string',
        ]);

        $user = $request->user();

        $cart = Cart::with('items.product.images')
            ->where('user_id', $user->id)
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Cart is empty'
            ], 422);
        }

        DB::beginTransaction();

        try {

            $subtotal = 0;

            foreach ($cart->items as $item) {

                if (!$item->product) {
                    throw new \Exception('Product not found');
                }

                if ($item->quantity > $item->product->stock_qty) {
                    throw new \Exception("Stock issue: {$item->product->name}");
                }

                $subtotal += $item->total_price;
            }

            // ✅ COUPON
            $discount = 0;
            $coupon = null;

            if ($request->filled('coupon_code')) {

                $coupon = Coupon::whereRaw('LOWER(code) = ?', [strtolower($request->coupon_code)])
                    ->where('status', 1)
                    ->whereDate('expiry_date', '>=', now())
                    ->first();

                if (!$coupon) {
                    throw new \Exception('Invalid coupon');
                }

                if ($coupon->min_amount && $subtotal < $coupon->min_amount) {
                    throw new \Exception('Minimum amount not reached');
                }

                $discount = $coupon->discount_type === 'percentage'
                    ? ($subtotal * $coupon->discount_value) / 100
                    : $coupon->discount_value;

                if ($coupon->max_discount) {
                    $discount = min($discount, $coupon->max_discount);
                }
            }

            $totalAmount = max(0, $subtotal - $discount);

            // ✅ CREATE ORDER
            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon?->id,
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            // ✅ ORDER ITEMS
            foreach ($cart->items as $item) {

                $product = $item->product;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'product_image' => $product->image,
                    'ratti' => $item->ratti,
                    'quantity' => $item->quantity,
                    'price' => $item->price_at_time,
                    'total' => $item->total_price,
                ]);

                // stock update
                $product->stock_qty -= $item->quantity;
                $product->stock_status = Product::resolveStockStatus($product->stock_qty);
                $product->save();
            }

            $cart->items()->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully',
                'data' => $this->formatOrder($order->id)
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // ✅ ORDER LIST
    public function index(Request $request)
    {
        $orders = Order::with(['items.product.images', 'coupon', 'addressData', 'payment'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $orders->map(fn($o) => $this->formatOrderData($o))
        ]);
    }

    // ✅ ORDER DETAIL
    public function show(Request $request, $id)
    {
        $order = Order::with(['items.product.images', 'coupon', 'addressData', 'payment'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $this->formatOrderData($order)
        ]);
    }

    // ✅ DELIVERED
    public function markDelivered($orderId)
    {
        $order = Order::findOrFail($orderId);

        if ($order->status === 'delivered') {
            return response()->json([
                'status' => false,
                'message' => 'Already delivered'
            ], 422);
        }

        $order->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Order delivered',
            'data' => $this->formatOrder($order->id)
        ]);
    }

    // ✅ FORMAT ORDER
    private function formatOrder($id)
    {
        $order = Order::with(['items.product.images', 'coupon', 'addressData', 'payment'])->find($id);
        return $this->formatOrderData($order);
    }

    private function formatOrderData($order)
    {
        return [

            // 🧾 BASIC
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'invoice_number' => $order->invoice_number,
            'hsn_code' => $order->hsn_code,
            'status' => $order->status,

            // 👤 USER
            'user_id' => $order->user_id,

            // 💰 PRICING
            'pricing' => [
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,

                'taxable_amount' => $order->taxable_amount,
                'gst_rate' => $order->gst_rate,
                'tax_type' => $order->tax_type,
                'cgst_amount' => $order->cgst_amount,
                'sgst_amount' => $order->sgst_amount,
                'igst_amount' => $order->igst_amount,

                'delivery_charge' => $order->delivery_charge,

                'cod_charge' => (float) (
                    $order->price_breakdown['cod_charge'] ?? 0
                ),

                'shipping_gst_rate' =>
                    $order->price_breakdown['shipping_gst_rate'] ?? 18,

                'shipping_gst_amount' =>
                    $order->price_breakdown['shipping_gst_amount'] ?? 0,

                'cod_gst_rate' =>
                    $order->price_breakdown['cod_gst_rate'] ?? 18,

                'cod_gst_amount' =>
                    $order->price_breakdown['cod_gst_amount'] ?? 0,

                'wallet_used' => $order->wallet_used,

                'paid_amount' => $order->paid_amount,

                'advance_paid_amount' => $order->advance_paid_amount,

                'remaining_cod_amount' => $order->remaining_cod_amount,

                'is_cod_advance' => (bool) $order->is_cod_advance,

                'total_amount' => $order->total_amount,
            ],

            // 💳 PAYMENT
            'payment' => $order->payment ? [
                'payment_id' => $order->payment->id,
                'transaction_id' => $order->payment->transaction_id,
                'gateway' => $order->payment->payment_gateway,
                'mode' => $order->payment->payment_mode,
                'advance_paid_amount' => $order->advance_paid_amount,
                'remaining_cod_amount' => $order->remaining_cod_amount,
                'amount' => $order->payment->amount,
                'currency' => $order->payment->currency,
                'status' => $order->payment->payment_status,
                'paid_at' => $order->paid_at,
            ] : [
                'payment_id' => null,
                'transaction_id' => 'WALLET-TXN-ORDER-' . $order->id,
                'gateway' => 'wallet',
                'mode' => 'wallet_only',
                'amount' => $order->wallet_used,
                'currency' => 'INR',
                'status' => 'success',
                'paid_at' => $order->paid_at,
            ],

            // 🔥 SHIPPING INFO
            'shipment_id' => $order->shipment_id,
            'awb_code' => $order->awb_code,
            'courier_name' => $order->courier_name,
            'shipping_status' => $order->shipping_status,

            // 📦 BOX DETAILS
            'total_weight' => $order->total_weight,
            'box_length' => $order->box_length,
            'box_breadth' => $order->box_breadth,
            'box_height' => $order->box_height,

            // 📍 ADDRESS
            'address' => [
                'snapshot' => [
                    'name' => $order->name,
                    'email' => $order->email,
                    'mobile' => $order->mobile,
                    'alternative_mobile' => $order->alternative_mobile,
                    'state_code' => $order->state_code,
                    'state' => $order->state,
                    'city' => $order->city,
                    'country' => $order->country,
                    'address' => $order->address,
                    'pincode' => $order->pincode,
                ],
                'current' => $order->addressData ? [
                    'id' => $order->addressData->id,
                    'name' => $order->addressData->name,
                    'email' => $order->addressData->email,
                    'mobile' => $order->addressData->mobile,
                    'alternative_mobile' => $order->addressData->alternative_mobile,
                    'state_code' => $order->addressData->state_code,
                    'state' => $order->addressData->state,
                    'city' => $order->addressData->city,
                    'country' => $order->addressData->country,
                    'address' => $order->addressData->address,
                    'pincode' => $order->addressData->pincode,
                ] : null,
            ],

            // 📦 ITEMS
            'items' => $order->items->map(function ($item) {

                $product = $item->product;

                return [
                    'product_id' => $item->product_id,

                    // SNAPSHOT
                    'name' => $item->product_name,
                    'slug' => $item->product_slug,
                    'image' => $item->product_image
                        ? asset('storage/product/' . $item->product_image)
                        : null,

                    // LIVE PRODUCT
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'description' => $product->description,
                        'stock' => $product->stock_qty,
                        'status' => $product->stock_status,
                        'images' => $product->images->map(fn($img) =>
                            asset('storage/product/' . $img->images)
                        ),
                    ] : null,

                    'ratti' => $item->ratti,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                    'gst_rate' => $product->gst_rate ?? 0,
                    'hsn_code' => $product->hsn_code ?? null,

                    // 📦 ITEM DIMENSIONS
                    'weight' => $item->weight,
                    'length' => $item->length,
                    'breadth' => $item->breadth,
                    'height' => $item->height,
                ];
            }),

            // 🎟 COUPON
            'coupon' => $order->coupon ? [
                'id' => $order->coupon->id,
                'code' => $order->coupon->code,
                'type' => $order->coupon->discount_type,
                'value' => $order->coupon->discount_value,
            ] : null,

            // 🧠 EXTRA
            'meta' => [
                'price_breakdown' => $order->price_breakdown,
            ],

            // 🕒 TIMELINE
            'timestamps' => [
                'created_at' => $order->created_at,
                'paid_at' => $order->paid_at,
                'delivered_at' => $order->delivered_at,
                'cancelled_at' => $order->cancelled_at,
            ],
        ];
    }

    public function uploadPdf(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'pdf' => 'required|string',
        ]);

        $order = Order::findOrFail($request->order_id);

        $base64Pdf = $request->pdf;

        if (str_contains($base64Pdf, ',')) {
            $base64Pdf = explode(',', $base64Pdf)[1];
        }

        $pdfContent = base64_decode($base64Pdf);

        if (!$pdfContent) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid PDF data'
            ], 422);
        }

        $fileName = 'invoice_' . $order->id . '.pdf';

        $filePath = 'invoices/' . $fileName;

        \Storage::disk('public')->put($filePath, $pdfContent);

        $order->update([
            'pdf' => $filePath
        ]);

        return response()->json([
            'status' => true,
            'message' => 'PDF uploaded successfully',
            'pdf' => asset('storage/' . $filePath)
        ]);
    }
}