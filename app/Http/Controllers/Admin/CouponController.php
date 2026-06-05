<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CouponController extends AdminController
{
    public function getIndex(Request $request)
    {
        return view('admin.coupons.index');
    }

    public function getList(Request $request)
    {
        $list = Coupon::with('employee')
            ->when($request->filled('id'),
                fn($q) => $q->where('id', $request->id)
            )
            ->when($request->filled('discount_type'),
                fn($q) => $q->where('discount_type', $request->discount_type)
            )
            ->when($request->filled('employee_id'),
                fn($q) => $q->where('employee_id', $request->employee_id)
            )
            ->when($request->status !== null && $request->status !== "",
                fn($q) => $q->where('status', $request->status)
            )
            ->when($request->is_visible !== null && $request->is_visible !== "",
                fn($q) => $q->where('is_visible', $request->is_visible)
            )
            ->orderByDesc('id');

        return \DataTables::of($list)
            ->editColumn('code', fn($row) =>
                '<strong>'.e($row->code).'</strong>'
            )
            ->editColumn('discount_type', function ($row) {
                return $row->discount_type === 'flat'
                    ? '<span class="badge bg-info">Flat</span>'
                    : '<span class="badge bg-primary">Percentage</span>';
            })
            ->editColumn('discount_value', function ($row) {
                return $row->discount_type === 'percentage'
                    ? $row->discount_value . ' %'
                    : '₹ ' . number_format($row->discount_value, 2);
            })
            ->editColumn('expiry_date', function ($row) {
                return Carbon::parse($row->expiry_date)->format('d M Y');
            })
            ->addColumn('employee', function ($row) {
                return $row->employee?->name ?? '-';
            })
            ->addColumn('status_label', function ($row) {
                return $row->status
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('visible_label', function ($row) {
                return $row->is_visible
                    ? '<span class="badge bg-success">Visible</span>'
                    : '<span class="badge bg-secondary">Hidden</span>';
            })
            ->rawColumns(['code','discount_type','status_label','visible_label'])
            ->make(true);
    }

    public function getCreate()
    {
        return view('admin.coupons.create');
    }

    public function postCreate(Request $request)
    {
        $request->validate([
            'employee_id'     => 'nullable|exists:users,id',
            'code'            => 'required|string|max:50|unique:coupons,code',
            'discount_type'   => 'required|in:flat,percentage',
            'discount_value'  => 'required|numeric|min:0.01',
            'min_amount'      => 'nullable|numeric|min:0',
            'max_discount'    => 'nullable|numeric|min:0',
            'expiry_date'     => 'required|date|after:today',
            'status'          => 'nullable|in:0,1',
            'is_visible'      => 'nullable|in:0,1',
        ]);

        if ($request->discount_type === 'percentage') {
            if ($request->discount_value > 100) {
                return response()->json([
                    'message' => 'Percentage discount cannot exceed 100%'
                ], 422);
            }
            if (!$request->max_discount) {
                return response()->json([
                    'message' => 'Maximum discount is required for percentage type'
                ], 422);
            }
        } else {
            $request->merge(['max_discount' => null]);
        }

        $coupon = Coupon::create([
            'employee_id'    => $request->employee_id ?? 1,
            'code'           => strtoupper(trim($request->code)),
            'discount_type'  => $request->discount_type,
            'discount_value' => $request->discount_value,
            'min_amount'     => $request->min_amount ?? 0,
            'max_discount'   => $request->max_discount,
            'expiry_date'    => $request->expiry_date,
            'status'         => $request->status ?? 1,
            'is_visible'     => $request->is_visible ?? 1,
        ]);

        if ($coupon->employee && $coupon->employee->email) {
            try {
                \Mail::to($coupon->employee->email)
                    ->send(
                        new \App\Mail\AffiliateCouponAssignedMail(
                            $coupon->employee,
                            $coupon
                        )
                    );
            } catch (\Exception $e) {
                \Log::error('Coupon mail failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Coupon created successfully and sent to affiliate email.'
        ]);
    }

    public function getUpdate(Request $request)
    {
        $coupon = Coupon::findOrFail($request->id);
        return view('admin.coupons.update', compact('coupon'));
    }

    public function postUpdate(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $request->validate([
            'employee_id'    => 'nullable|exists:users,id',
            'code'           => 'required|string|max:50|unique:coupons,code,'.$coupon->id,
            'discount_type'  => 'required|in:flat,percentage',
            'discount_value' => 'required|numeric|min:0.01',
            'min_amount'     => 'nullable|numeric|min:0',
            'max_discount'   => 'nullable|numeric|min:0',
            'expiry_date'    => 'required|date',
            'status'         => 'nullable|in:0,1',
            'is_visible'     => 'nullable|in:0,1',
        ]);

        if ($request->discount_type === 'percentage') {
            if ($request->discount_value > 100) {
                return response()->json([
                    'message' => 'Percentage discount cannot exceed 100%'
                ], 422);
            }
            if (!$request->max_discount) {
                return response()->json([
                    'message' => 'Maximum discount is required for percentage type'
                ], 422);
            }

        } else {
            $request->merge(['max_discount' => null]);
        }

        $coupon->update([
            'employee_id'    => $request->employee_id ?? 1,
            'code'           => strtoupper(trim($request->code)),
            'discount_type'  => $request->discount_type,
            'discount_value' => $request->discount_value,
            'min_amount'     => $request->min_amount ?? 0,
            'max_discount'   => $request->max_discount,
            'expiry_date'    => $request->expiry_date,
            'status'         => $request->status ?? 1,
            'is_visible'     => $request->is_visible ?? 1,
        ]);

        $coupon->refresh();

        if ($coupon->employee && $coupon->employee->email) {
            try {
                \Mail::to($coupon->employee->email)
                    ->send(
                        new \App\Mail\AffiliateCouponAssignedMail(
                            $coupon->employee,
                            $coupon
                        )
                    );
            } catch (\Exception $e) {
                \Log::error('Coupon mail failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Coupon updated successfully and updated details emailed to affiliate.'
        ]);
    }

    public function getDelete(Request $request)
    {
        Coupon::findOrFail($request->id)->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully'
        ]);
    }

    public function getChangeStatus(Request $request)
    {
        $coupon = Coupon::findOrFail($request->id);
        $coupon->update([
            'status' => !$coupon->status
        ]);

        return response()->json([
            'message' => 'Status updated successfully'
        ]);
    }

    public function getChangeVisible(Request $request)
    {
        $coupon = Coupon::findOrFail($request->id);

        $coupon->update([
            'is_visible' => !$coupon->is_visible
        ]);

        return response()->json([
            'message' => 'Visibility updated successfully'
        ]);
    }
}
