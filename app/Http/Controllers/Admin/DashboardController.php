<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends AdminController
{
    public function getIndex(Request $request)
    {
        return view('admin.dashboard.index');
    }

    public function getStats(Request $request)
    {
        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        return response()->json([

            'total_users' => User::where('type', 'user')->count(),

            'online_users' => User::where('type', 'user')
                ->where('is_online', 1)
                ->count(),

            'total_orders' => Order::whereBetween('created_at', [$start, $end])
                ->count(),

            'pending_orders' => Order::where('status', 'pending')
                ->whereBetween('created_at', [$start, $end])
                ->count(),

            'delivered_orders' => Order::where('status', 'delivered')
                ->whereBetween('delivered_at', [$start, $end])
                ->count(),

            'cancelled_orders' => Order::where('status', 'cancelled')
                ->whereBetween('cancelled_at', [$start, $end])
                ->count(),

            'total_products' => Product::count(),

            'prepaid_collected' => Order::whereNotIn('status', ['cancelled', 'rto'])
                ->whereNotNull('paid_at')
                ->whereBetween('paid_at', [$start, $end])
                ->sum('paid_amount') ?? 0,

            'cod_collected' => Order::where('status', 'delivered')
                ->whereBetween('delivered_at', [$start, $end])
                ->selectRaw("SUM(total_amount - paid_amount) as total")
                ->value('total') ?? 0,

            'cod_pending' => Order::whereNotIn('status', ['cancelled', 'delivered', 'rto'])
                ->selectRaw("SUM(total_amount - paid_amount) as total")
                ->value('total') ?? 0,
        ]);
    }

    public function getSalesGraph(Request $request)
    {
        $start = Carbon::parse($request->start_date)->startOfDay()->toDateString();
        $end   = Carbon::parse($request->end_date)->endOfDay()->toDateString();

        $dates = $this->getDaysBetweenDates($start, $end);

        $orders = Order::selectRaw("COUNT(id) as total, DATE(created_at) as date")
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $revenues = Order::selectRaw("SUM(paid_amount) as total, DATE(created_at) as date")
            ->where('status', '!=', 'cancelled')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $orderData = [];
        $revenueData = [];

        foreach ($dates['dates'] as $date) {
            $normalized = Carbon::parse($date)->toDateString();

            $labels[]      = $normalized;
            $orderData[]   = (int) ($orders[$normalized] ?? 0);
            $revenueData[] = (float) ($revenues[$normalized] ?? 0);
        }

        return response()->json([
            'labels'  => $labels,
            'orders'  => $orderData,
            'revenue' => $revenueData,
        ]);
    }

    public function getUserGraph(Request $request)
    {
        $start = Carbon::parse($request->start_date)->startOfDay()->toDateString();
        $end   = Carbon::parse($request->end_date)->endOfDay()->toDateString();

        $dates = $this->getDaysBetweenDates($start, $end);

        $registrations = User::selectRaw("COUNT(id) as total, DATE(created_at) as date")
            ->where('type', 'user')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $onlineUsers = User::selectRaw("COUNT(id) as total, DATE(last_seen_at) as date")
            ->where('type', 'user')
            ->where('is_online', 1)
            ->whereDate('last_seen_at', '>=', $start)
            ->whereDate('last_seen_at', '<=', $end)
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $registerData = [];
        $onlineData = [];

        foreach ($dates['dates'] as $date) {
            $normalized = Carbon::parse($date)->toDateString();

            $labels[]       = $normalized;
            $registerData[] = (int) ($registrations[$normalized] ?? 0);
            $onlineData[]   = (int) ($onlineUsers[$normalized] ?? 0);
        }

        return response()->json([
            'labels'        => $labels,
            'registrations' => $registerData,
            'online_users'  => $onlineData,
        ]);
    }

    public function getTopProducts(Request $request)
    {
        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        $products = OrderItem::select(
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.total) as revenue')
            )
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.created_at', [$start, $end])
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    public function getLowStockProducts()
    {
        $products = Product::where('stock_qty', '<=', 5)
            ->orderBy('stock_qty')
            ->limit(10)
            ->get(['id', 'name', 'stock_qty']);

        return response()->json($products);
    }

    public function getStatusBreakdown(Request $request)
    {
        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        $dateColumnByStatus = [
            'pending'   => 'created_at',
            'paid'      => 'created_at',
            'packed'    => 'created_at',
            'shipped'   => 'created_at',
            'rto'       => 'rto_at',
            'delivered' => 'delivered_at',
            'cancelled' => 'cancelled_at',
        ];

        $result = [];
        foreach ($dateColumnByStatus as $status => $dateColumn) {
            $count = Order::where('status', $status)
                ->whereBetween($dateColumn, [$start, $end])
                ->count();

            $result[] = [
                'status' => $status,
                'count'  => $count,
            ];
        }

        return response()->json($result);
    }

    private function getDaysBetweenDates($start, $end)
    {
        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);
        $dates = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return ['dates' => $dates];
    }
}