<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\user;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Mail\OrderDeliveryTrackMail;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function getIndex()
    {
        return view('admin.orders.index');
    }

    public function getList(Request $request)
    {
        $orders = Order::with(['user', 'coupon', 'items.product.category'])
            ->when($request->user_id, function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->category_id, function ($q) use ($request) {
                $q->whereHas('items.product', function ($p) use ($request) {
                    $p->where('category_id', $request->category_id);
                });
            })
            ->when($request->product_id, function ($q) use ($request) {
                $q->whereHas('items', function ($i) use ($request) {
                    $i->where('product_id', $request->product_id);
                });
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when(auth()->user()->type == 'employee', function ($q) {
                $q->whereHas('coupon', function ($coupon) {
                    $coupon->where('employee_id', auth()->id());
                });
            })
            ->latest();

        return datatables()->of($orders)
            ->addColumn('order_no', function ($o) {
                return \Illuminate\Support\Str::limit($o->order_number, 20);
            })

            ->addColumn('user', function ($o) {
                $code = \Illuminate\Support\Str::limit($o->user->code, 10);
                $name = \Illuminate\Support\Str::limit($o->user->name, 10);
        
                return '[ <b>'.$code.'</b> ]<br>'.$name;
            })
            
            ->addColumn('category', function ($o) {
            
                return $o->items
                    ->pluck('product.category.name')
                    ->unique()
                    ->map(function ($name) {
                        return \Illuminate\Support\Str::limit($name, 10);
                    })
                    ->implode(', ');
            })
            
            ->addColumn('products', function ($order) {
                return $order->items->map(function ($item) {
                    $name = \Illuminate\Support\Str::limit($item->product_name, 10);
                    return '<div class="mb-1">'.$name.'</div>';
                })->implode('');
            })
            ->addColumn('items_count', function ($order) {
                return '<span class="fw-bold">'.$order->items->count().'</span>';
            })
            ->addColumn('amount', fn ($o) => '₹ '.number_format($o->total_amount, 2))
            ->addColumn('status', function ($o) {
                return match ($o->status) {
                    'pending'   => '<span class="badge bg-warning">Pending</span>',
                    'paid'      => '<span class="badge bg-info">Paid</span>',
                    'packed'    => '<span class="badge bg-primary">Packed</span>',
                    'shipped'   => '<span class="badge bg-dark">Shipped</span>',
                    'delivered' => '<span class="badge bg-success">Delivered</span>',
                    'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
                    'rto'       => '<span class="badge bg-dark text-warning">RTO</span>',
                    default     => $o->status
                };
            })
            ->addColumn('created_at', fn ($o) =>
                $o->created_at->format('d M Y h:i A')
            )
            ->rawColumns(['user', 'products', 'status'])
            ->make(true);
    }

    public function getView(Request $request, $id)
    {
        $order = Order::with([
            'user',
            'payment',
            'coupon',
            'addressData',
            'items',
            'items.product' => function ($q) {
                $q->withTrashed();
            },
            'items.product.images',
            'items.product.category',
            'items.product.storeReviews' => function ($q) {
                $q->select('id','product_id','user_id','rating','review');
            }
        ])->findOrFail($id);

        if (
            auth()->user()->type == 'employee' &&
            (
                !$order->coupon ||
                $order->coupon->employee_id != auth()->id()
            )
        ) {
            abort(403);
        }

        return view('admin.orders.view', compact('order'));
    }
    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,packed,shipped,rto,cancelled',
            'cancel_reason' => 'required_if:status,cancelled|nullable|string|max:1000',
        ]);

        $order = Order::with('coupon')->findOrFail($id);

        // Employee permission check
        if (
            auth()->user()->type == 'employee' &&
            (
                !$order->coupon ||
                $order->coupon->employee_id != auth()->id()
            )
        ) {
            abort(403);
        }

        $status = $request->status;

        $updateData = [
            'status' => $status,
            'shipping_status' => $status,
        ];

        /*
        |--------------------------------------------------------------------------
        | RTO
        |--------------------------------------------------------------------------
        */
        if ($status === 'rto') {
            $updateData['rto_at'] = $order->rto_at ?? now();
        } else {
            $updateData['rto_at'] = null;
        }

        /*
        |--------------------------------------------------------------------------
        | CANCELLED
        |--------------------------------------------------------------------------
        */
        if ($status === 'cancelled') {
            $updateData['cancelled_at'] = $order->cancelled_at ?? now();
            $updateData['cancel_reason'] = trim($request->cancel_reason);
        } else {
            $updateData['cancelled_at'] = null;
            $updateData['cancel_reason'] = null;
        }

        $order->update($updateData);

        return back()->with(
            'success',
            'Order status updated successfully.'
        );
    }

    public function sendMail(Request $request, $id)
    {
        $request->validate([
            'awb_code' => 'required|min:5'
        ]);

        $order = Order::with('user')->findOrFail($id);

        $order->update([
            'awb_code' => trim($request->awb_code)
        ]);

        Mail::to($order->email ?? $order->user->email)
            ->send(new OrderDeliveryTrackMail($order));

        return back()->with(
            'success',
            'Tracking mail sent successfully.'
        );
    }
}