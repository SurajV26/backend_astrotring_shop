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
        $start = Carbon::parse($request->start_date)
            ->startOfDay();

        $end = Carbon::parse($request->end_date)
            ->endOfDay();

        return response()->json([

            'total_users' => User::where('type', 'user')
                // ->whereBetween('created_at', [$start, $end])
                ->count(),

            'online_users' => User::where('type', 'user')
                ->where('is_online', 1)
                // ->whereBetween('updated_at', [$start, $end])
                ->count(),

            'total_orders' => Order::whereBetween('created_at', [$start, $end])
                ->count(),

            'pending_orders' => Order::whereIn('status', [
                    'pending',
                    'paid',
                    'packed',
                    'shipped',
                    'delivered',
                    'cancelled'
                ])
                ->where('status', '!=', 'cancelled')
                ->whereBetween('created_at', [$start, $end])
                ->count(),

            'delivered_orders' => Order::where('status', 'delivered')
                ->where('status', '!=', 'cancelled')
                ->whereBetween('created_at', [$start, $end])
                ->count(),

            'cancelled_orders' => Order::where('status', 'cancelled')
                ->whereBetween('created_at', [$start, $end])
                ->count(),

            'total_products' => Product::count(),

            'total_revenue' => Order::where('status', '!=', 'cancelled')
                ->whereBetween('created_at', [$start, $end])
                ->sum('paid_amount'),
        ]);
    }

    public function getSalesGraph(Request $request)
    {
        $start = Carbon::parse($request->start_date)->startOfDay()->toDateString();

        $end = Carbon::parse($request->end_date)
            ->endOfDay()
            ->toDateString();

        $dates = $this->getDaysBetweenDates($start, $end);

        $orders = Order::selectRaw("
                COUNT(id) as total,
                DATE(created_at) as date
            ")
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $revenues = Order::selectRaw("
            SUM(paid_amount) as total,
            DATE(created_at) as date
        ")
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

            $labels[] = $normalized;

            $orderData[] = $orders[$normalized] ?? 0;

            $revenueData[] = $revenues[$normalized] ?? 0;
        }

        return response()->json([
            'labels' => $labels,
            'orders' => $orderData,
            'revenue' => $revenueData,
        ]);
    }

    public function getUserGraph(Request $request)
    {
        $start = Carbon::parse($request->start_date)->startOfDay()->toDateString();

        $end = Carbon::parse($request->end_date)
            ->endOfDay()
            ->toDateString();

        $dates = $this->getDaysBetweenDates($start, $end);

        $registrations = User::selectRaw("
                COUNT(id) as total,
                DATE(created_at) as date
            ")
            ->where('type', 'user')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $onlineUsers = User::selectRaw("
                COUNT(id) as total,
                DATE(updated_at) as date
            ")
            ->where('type', 'user')
            ->where('is_online', 1)
            ->whereDate('updated_at', '>=', $start)
            ->whereDate('updated_at', '<=', $end)
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $registerData = [];
        $onlineData = [];

        foreach ($dates['dates'] as $date) {

            $normalized = Carbon::parse($date)->toDateString();

            $labels[] = $normalized;

            $registerData[] = $registrations[$normalized] ?? 0;

            $onlineData[] = $onlineUsers[$normalized] ?? 0;
        }

        return response()->json([
            'labels' => $labels,
            'registrations' => $registerData,
            'online_users' => $onlineData,
        ]);
    }

    public function getTopProducts()
    {
        return OrderItem::select(
                'product_name',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();
    }

    public function getLowStockProducts()
    {
        return Product::where('stock_qty', '<=', 5)
            ->orderBy('stock_qty')
            ->limit(10)
            ->get();
    }


    function getDaysBetweenDates($start, $end)
    {
        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);
        $dates = [];

        for ($date = $start; $date->lte($end); $date->addDay()) {
            $dates[] = $date->toDateString(); // YYYY-MM-DD
        }

        return ['dates' => $dates];
    }
}