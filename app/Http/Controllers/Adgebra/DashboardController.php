<?php

namespace App\Http\Controllers\Adgebra;

use App\Exports\AdgebraCartsExport;
use App\Exports\AdgebraOrdersExport;
use App\Exports\AdgebraUsersExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only Adgebra partner reporting.
 * Every report starts from an Adgebra-identified visit; no general store data is exposed.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('adgebra.users', $request->query());
    }

    public function users(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->usersQuery($filters);
        $users = (clone $query)->orderByDesc('last_identified_at')->paginate(25)->withQueryString();

        $users->getCollection()->transform(function ($row) {
            $row->mobile_masked = $this->maskMobile($row->mobile ?? null);
            $row->email_masked = $this->maskEmail($row->email ?? null);
            return $row;
        });

        $totalUsers = $this->countRows($query);
        $totalVisits = $this->identifiedVisitsQuery($filters)->count();

        return view('adgebra.users', [
            'partner' => $request->attributes->get('adgebra_partner'),
            'filters' => $filters,
            'users' => $users,
            'totalUsers' => $totalUsers,
            'totalVisits' => $totalVisits,
        ]);
    }

    public function carts(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->cartsQuery($filters);
        $carts = (clone $query)->orderByDesc('last_cart_activity')->paginate(25)->withQueryString();

        $carts->getCollection()->transform(function ($row) {
            $row->mobile_masked = $this->maskMobile($row->mobile ?? null);
            $row->email_masked = $this->maskEmail($row->email ?? null);
            return $row;
        });

        $totalCarts = $this->countRows($query);
        $cartValue = (float) DB::query()->fromSub(clone $query, 'cart_report')->sum('cart_value');

        return view('adgebra.carts', [
            'partner' => $request->attributes->get('adgebra_partner'),
            'filters' => $filters,
            'carts' => $carts,
            'totalCarts' => $totalCarts,
            'cartValue' => $cartValue,
        ]);
    }

    public function orders(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->ordersQuery($filters);
        $orders = (clone $query)->orderByDesc('order_date')->paginate(25)->withQueryString();

        $orders->getCollection()->transform(function ($row) {
            $row->mobile_masked = $this->maskMobile($row->mobile ?? null);
            $row->email_masked = $this->maskEmail($row->email ?? null);
            return $row;
        });

        $totalOrders = $this->countRows($query);
        $orderValue = (float) DB::query()->fromSub(clone $query, 'order_report')->sum('total_amount');

        return view('adgebra.orders', [
            'partner' => $request->attributes->get('adgebra_partner'),
            'filters' => $filters,
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'orderValue' => $orderValue,
        ]);
    }

    public function exportUsersCsv(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->usersQuery($filters);

        return $this->streamCsv(
            'adgebra-users-' . $filters['from'] . '-to-' . $filters['to'] . '.csv',
            ['User ID', 'Customer', 'Mobile (masked)', 'Email (masked)', 'Registered At', 'First Identified', 'Last Identified', 'Landing Pages', 'Campaign IDs', 'Placement IDs', 'Identified Visits'],
            $query,
            fn ($row) => [
                $row->user_id,
                $this->safeCsvText($row->customer_name),
                $this->maskMobile($row->mobile ?? null),
                $this->maskEmail($row->email ?? null),
                $row->registered_at,
                $row->first_identified_at,
                $row->last_identified_at,
                $this->safeCsvText($row->landing_pages),
                $this->safeCsvText($row->campaign_ids),
                $this->safeCsvText($row->placement_ids),
                (int) $row->linked_visits,
            ],
            'last_identified_at',
            'user_id'
        );
    }

    public function exportUsersExcel(Request $request)
    {
        $filters = $this->filters($request);
        return Excel::download(
            new AdgebraUsersExport($this->usersQuery($filters)),
            'adgebra-users-' . $filters['from'] . '-to-' . $filters['to'] . '.xlsx'
        );
    }

    public function exportCartsCsv(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->cartsQuery($filters);

        return $this->streamCsv(
            'adgebra-carts-' . $filters['from'] . '-to-' . $filters['to'] . '.csv',
            ['Cart ID', 'User ID', 'Customer', 'Mobile (masked)', 'Email (masked)', 'Products', 'Product Lines', 'Quantity', 'Cart Value (INR)', 'Last Cart Activity', 'Landing Pages', 'Campaign IDs', 'Placement IDs', 'Creative IDs'],
            $query,
            fn ($row) => [
                $row->cart_id,
                $row->user_id,
                $this->safeCsvText($row->customer_name),
                $this->maskMobile($row->mobile ?? null),
                $this->maskEmail($row->email ?? null),
                $this->safeCsvText($row->products),
                (int) $row->product_lines,
                (int) $row->total_quantity,
                number_format((float) $row->cart_value, 2, '.', ''),
                $row->last_cart_activity,
                $this->safeCsvText($row->landing_pages),
                $this->safeCsvText($row->campaign_ids),
                $this->safeCsvText($row->placement_ids),
                $this->safeCsvText($row->creative_ids),
            ],
            'last_cart_activity',
            'cart_id'
        );
    }

    public function exportCartsExcel(Request $request)
    {
        $filters = $this->filters($request);
        return Excel::download(
            new AdgebraCartsExport($this->cartsQuery($filters)),
            'adgebra-carts-' . $filters['from'] . '-to-' . $filters['to'] . '.xlsx'
        );
    }

    public function exportOrdersCsv(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->ordersQuery($filters);

        return $this->streamCsv(
            'adgebra-orders-' . $filters['from'] . '-to-' . $filters['to'] . '.csv',
            ['Order ID', 'Order Number', 'Order Date', 'User ID', 'Customer', 'Mobile (masked)', 'Email (masked)', 'Products', 'Order Amount (INR)', 'Order Status', 'Payment Method', 'Payment Status', 'Transaction ID', 'Landing Page', 'Campaign ID', 'Placement ID', 'Creative ID', 'Click ID'],
            $query,
            fn ($row) => [
                $row->order_id,
                $this->safeCsvText($row->order_number),
                $row->order_date,
                $row->user_id,
                $this->safeCsvText($row->customer_name),
                $this->maskMobile($row->mobile ?? null),
                $this->maskEmail($row->email ?? null),
                $this->safeCsvText($row->products),
                number_format((float) $row->total_amount, 2, '.', ''),
                $this->safeCsvText($row->order_status),
                $this->safeCsvText($row->payment_method),
                $this->safeCsvText($row->payment_status),
                $this->safeCsvText($row->transaction_id),
                $this->safeCsvText($row->landing_page),
                $this->safeCsvText($row->campaign_id),
                $this->safeCsvText($row->placement_id),
                $this->safeCsvText($row->creative_id),
                $this->safeCsvText($row->click_id),
            ],
            'order_date',
            'order_id'
        );
    }

    public function exportOrdersExcel(Request $request)
    {
        $filters = $this->filters($request);
        return Excel::download(
            new AdgebraOrdersExport($this->ordersQuery($filters)),
            'adgebra-orders-' . $filters['from'] . '-to-' . $filters['to'] . '.xlsx'
        );
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'landing_page' => ['nullable', 'in:dhan-yog,rashi,rudraksha,bracelets,combos'],
            'campaign_id' => ['nullable', 'string', 'max:191'],
            'placement_id' => ['nullable', 'string', 'max:191'],
            'creative_id' => ['nullable', 'string', 'max:191'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $from = $validated['from'] ?? now()->subDays(29)->toDateString();
        $to = $validated['to'] ?? now()->toDateString();
        if (Carbon::parse($from)->gt(Carbon::parse($to))) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from,
            'to' => $to,
            'from_start' => $from . ' 00:00:00',
            'to_end' => $to . ' 23:59:59',
            'landing_page' => $validated['landing_page'] ?? '',
            'campaign_id' => trim((string) ($validated['campaign_id'] ?? '')),
            'placement_id' => trim((string) ($validated['placement_id'] ?? '')),
            'creative_id' => trim((string) ($validated['creative_id'] ?? '')),
            'search' => trim((string) ($validated['search'] ?? '')),
        ];
    }

    private function identifiedVisitsQuery(array $filters): Builder
    {
        $query = DB::table('adgebra_visits as v')
            ->whereNotNull('v.user_id')
            ->whereNotNull('v.identified_at')
            ->where('v.source', 'Adgebra')
            ->whereBetween('v.identified_at', [$filters['from_start'], $filters['to_end']]);

        $this->applyVisitFilters($query, $filters);
        return $query;
    }

    private function usersQuery(array $filters): Builder
    {
        $query = $this->identifiedVisitsQuery($filters)
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->where('u.type', 'user')
            ->whereNull('u.deleted_at')
            ->select([
                'u.id as user_id',
                'u.name as customer_name',
                'u.mobile',
                'u.email',
                'u.created_at as registered_at',
            ])
            ->selectRaw('MIN(v.identified_at) as first_identified_at')
            ->selectRaw('MAX(v.identified_at) as last_identified_at')
            ->selectRaw('COUNT(DISTINCT v.id) as linked_visits')
            ->selectRaw("GROUP_CONCAT(DISTINCT v.landing_page ORDER BY v.landing_page SEPARATOR ', ') as landing_pages")
            ->selectRaw("GROUP_CONCAT(DISTINCT v.campaign_id ORDER BY v.campaign_id SEPARATOR ', ') as campaign_ids")
            ->selectRaw("GROUP_CONCAT(DISTINCT v.placement_id ORDER BY v.placement_id SEPARATOR ', ') as placement_ids")
            ->groupBy('u.id', 'u.name', 'u.mobile', 'u.email', 'u.created_at');

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('u.name', 'like', $search)
                    ->orWhere('u.email', 'like', $search)
                    ->orWhere('u.mobile', 'like', $search)
                    ->orWhere('u.id', 'like', $search);
            });
        }

        return $query;
    }

    /** One row per qualified user; aggregated visit dimensions prevent cart/order joins multiplying rows. */
    private function eligibleUsersQuery(array $filters): Builder
    {
        return $this->identifiedVisitsQuery($filters)
            ->select('v.user_id')
            ->selectRaw("GROUP_CONCAT(DISTINCT v.landing_page ORDER BY v.landing_page SEPARATOR ', ') as landing_pages")
            ->selectRaw("GROUP_CONCAT(DISTINCT v.campaign_id ORDER BY v.campaign_id SEPARATOR ', ') as campaign_ids")
            ->selectRaw("GROUP_CONCAT(DISTINCT v.placement_id ORDER BY v.placement_id SEPARATOR ', ') as placement_ids")
            ->selectRaw("GROUP_CONCAT(DISTINCT v.creative_id ORDER BY v.creative_id SEPARATOR ', ') as creative_ids")
            ->groupBy('v.user_id');
    }

    private function cartsQuery(array $filters): Builder
    {
        $eligibleUsers = $this->eligibleUsersQuery($filters);

        $query = DB::table('carts as c')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->where('u.type', 'user')
            ->whereNull('u.deleted_at')
            ->joinSub($eligibleUsers, 'eligible', function ($join) {
                $join->on('eligible.user_id', '=', 'u.id');
            })
            ->join('cart_items as ci', 'ci.cart_id', '=', 'c.id')
            ->leftJoin('products as p', 'p.id', '=', 'ci.product_id')
            ->whereBetween('ci.updated_at', [$filters['from_start'], $filters['to_end']])
            ->select([
                'c.id as cart_id',
                'u.id as user_id',
                'u.name as customer_name',
                'u.mobile',
                'u.email',
                'eligible.landing_pages',
                'eligible.campaign_ids',
                'eligible.placement_ids',
                'eligible.creative_ids',
            ])
            ->selectRaw('COUNT(DISTINCT ci.id) as product_lines')
            ->selectRaw('COALESCE(SUM(ci.quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(ci.total_price), 0) as cart_value')
            ->selectRaw('MAX(ci.updated_at) as last_cart_activity')
            ->selectRaw("GROUP_CONCAT(CONCAT(COALESCE(p.name, CONCAT('Product #', ci.product_id)), ' x', ci.quantity) ORDER BY ci.id SEPARATOR ', ') as products")
            ->groupBy(
                'c.id', 'u.id', 'u.name', 'u.mobile', 'u.email',
                'eligible.landing_pages', 'eligible.campaign_ids',
                'eligible.placement_ids', 'eligible.creative_ids'
            );

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('u.name', 'like', $search)
                    ->orWhere('u.email', 'like', $search)
                    ->orWhere('u.mobile', 'like', $search)
                    ->orWhere('u.id', 'like', $search)
                    ->orWhere('p.name', 'like', $search)
                    ->orWhere('c.id', 'like', $search);
            });
        }

        return $query;
    }

    private function ordersQuery(array $filters): Builder
    {
        $items = DB::table('order_items as oi')
            ->select('oi.order_id')
            ->selectRaw("GROUP_CONCAT(CONCAT(oi.product_name, ' x', oi.quantity) ORDER BY oi.id SEPARATOR ', ') as products")
            ->selectRaw('SUM(oi.quantity) as total_quantity')
            ->groupBy('oi.order_id');

        $query = DB::table('adgebra_order_attributions as a')
            ->join('adgebra_visits as v', 'v.id', '=', 'a.visit_id')
            ->join('orders as o', 'o.id', '=', 'a.order_id')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->where('u.type', 'user')
            ->whereNull('u.deleted_at')
            ->leftJoin('payments as p', 'p.id', '=', 'o.payment_id')
            ->leftJoinSub($items, 'item_summary', function ($join) {
                $join->on('item_summary.order_id', '=', 'o.id');
            })
            ->whereColumn('v.user_id', 'o.user_id')
            ->whereNotNull('v.identified_at')
            ->where('v.source', 'Adgebra')
            ->whereBetween('o.created_at', [$filters['from_start'], $filters['to_end']]);

        $this->applyVisitFilters($query, $filters);

        $query->select([
            'o.id as order_id',
            'o.order_number',
            'o.created_at as order_date',
            'o.user_id',
            'u.name as customer_name',
            'u.mobile',
            'u.email',
            'o.total_amount',
            'o.status as order_status',
            'p.payment_gateway as payment_method',
            'p.payment_status',
            'p.transaction_id',
            'item_summary.products',
            'item_summary.total_quantity',
            'v.landing_page',
            'v.campaign_id',
            'v.placement_id',
            'v.creative_id',
            'v.click_id',
        ]);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('o.order_number', 'like', $search)
                    ->orWhere('u.name', 'like', $search)
                    ->orWhere('u.email', 'like', $search)
                    ->orWhere('u.mobile', 'like', $search)
                    ->orWhere('o.id', 'like', $search)
                    ->orWhere('item_summary.products', 'like', $search);
            });
        }

        return $query;
    }

    private function applyVisitFilters(Builder $query, array $filters): void
    {
        if ($filters['landing_page'] !== '') {
            $query->where('v.landing_page', $filters['landing_page']);
        }
        if ($filters['campaign_id'] !== '') {
            $query->where('v.campaign_id', $filters['campaign_id']);
        }
        if ($filters['placement_id'] !== '') {
            $query->where('v.placement_id', $filters['placement_id']);
        }
        if ($filters['creative_id'] !== '') {
            $query->where('v.creative_id', $filters['creative_id']);
        }
    }

    private function countRows(Builder $query): int
    {
        return (int) DB::query()->fromSub(clone $query, 'report_count')->count();
    }

    private function streamCsv(
        string $filename,
        array $headings,
        Builder $query,
        callable $mapper,
        string $orderColumn,
        string $tieBreakerColumn
    ): StreamedResponse {
        return response()->streamDownload(function () use ($headings, $query, $mapper, $orderColumn, $tieBreakerColumn) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Unable to open CSV output stream.');
            }

            // Excel-friendly UTF-8 BOM.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headings);

            // A unique secondary key prevents rows with identical timestamps
            // from moving between chunks while the export is being read.
            $query->orderBy($orderColumn, 'desc')
                ->orderBy($tieBreakerColumn, 'desc')
                ->chunk(500, function ($rows) use ($output, $mapper) {
                foreach ($rows as $row) {
                    fputcsv($output, $mapper($row));
                }
            });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private, max-age=0',
        ]);
    }

    private function safeCsvText($value): string
    {
        $text = (string) ($value ?? '');
        return preg_match('/^[\s\x00-\x1F]*[=+@\-]/u', $text) ? "'" . $text : $text;
    }

    private function maskMobile(?string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);
        if ($digits === '') return '—';
        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }

    private function maskEmail(?string $email): string
    {
        $email = trim((string) $email);
        if ($email === '' || !str_contains($email, '@')) return '—';
        [$local, $domain] = explode('@', $email, 2);
        return mb_substr($local, 0, 1) . str_repeat('*', max(2, mb_strlen($local) - 1)) . '@' . $domain;
    }
}
