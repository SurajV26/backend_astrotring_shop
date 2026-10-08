<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\EmployeeCommission;
use Illuminate\Http\Request;

class EmployeeEarningController extends AdminController
{
    public function getIndex()
    {
        $availableCommission = 0;

        if (auth()->user()->type == 'employee') {
            $availableCommission = \App\Models\EmployeeCommission::where(
                'employee_id',
                auth()->id()
            )
            ->where('status', 'pending')
            ->where('is_withdraw_requested', 0)
            ->whereHas('order', function ($q) {
                $q->where(
                    'delivered_at',
                    '<=',
                    now()->subDays(15)
                );
            })
            ->sum('commission_amount');
        }

        return view('admin.employee_earnings.index', compact('availableCommission'));
    }

    public function getList(Request $request)
    {
        $list = EmployeeCommission::with([
            'employee',
            'order',
            'coupon'
        ])
        ->when(
            auth()->user()->type == 'employee',
            fn($q) => $q->where(
                'employee_id',
                auth()->id()
            )
        )
        ->when(
            $request->employee_id,
            fn($q) => $q->where(
                'employee_id',
                $request->employee_id
            )
        )
        ->when(
            $request->status,
            fn($q) => $q->where(
                'status',
                $request->status
            )
        )
        ->latest();

        return \DataTables::of($list)
            ->addColumn('code_name', function ($row) {

                return '[ <b>' . e($row->employee->username) . '</b> ]<br>' . e($row->employee->name);
            })
            ->addColumn('order_number', function ($row) {

                return $row->order?->order_number ?? '-';

            })
            ->addColumn('coupon_code', function ($row) {

                return $row->coupon?->code ?? '-';

            })
            ->addColumn('order_amount', function ($row) {
                return '₹ ' .
                    number_format(
                        $row->order_amount,
                        2
                    );
            })
            ->addColumn('commission_percentage', function ($row) {
                return $row->commission_percentage.'%';
            })
            ->addColumn('commission_amount', function ($row) {
                return '₹ ' .
                    number_format(
                        $row->commission_amount,
                        2
                    );
            })
            ->addColumn('status_badge', function ($row) {
                return match ($row->status) {
                    'delivery_pending' =>
                        '<span class="badge bg-info">Delivery Pending</span>',
                    'pending' =>
                        '<span class="badge bg-warning">Commission Pending</span>',
                    'paid' =>
                        '<span class="badge bg-success">Paid</span>',
                    'cancelled' =>
                        '<span class="badge bg-danger">Cancelled</span>',
                    default =>
                        '<span class="badge bg-secondary">'
                        . e(ucfirst(str_replace('_', ' ', $row->status)))
                        . '</span>',
                };
            })
            ->addColumn('action', function ($row) {
                return '
                    <a href="'.
                        route(
                            'admin.employee_earnings.view',
                            $row->id
                        )
                    .'"
                    class="btn btn-sm btn-primary">
                        View
                    </a>';
            })
            ->rawColumns(['code_name', 'status_badge', 'action'])
            ->make(true);
    }

    public function getView(Request $request, $id)
    {
        $earning = EmployeeCommission::with([
            'employee',
            'order',
            'coupon'
        ])->findOrFail($id);

        if (
            auth()->user()->type == 'employee'
            &&
            $earning->employee_id != auth()->id()
        ) {
            abort(403);
        }

        return view(
            'admin.employee_earnings.view',
            compact('earning')
        );
    }

    public function markPaid($id)
    {

        if (auth()->user()->type == 'employee') {
            abort(403);
        }

        $earning = EmployeeCommission::findOrFail($id);

        if ($earning->status != 'pending') {
            return back()->with('error', 'Invalid status');
        }

        $earning->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_by' => auth()->id(),
        ]);

        return back()->with(
            'success',
            'Commission marked as paid.'
        );
    }
}