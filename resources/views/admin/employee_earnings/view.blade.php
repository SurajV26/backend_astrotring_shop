@extends('layouts.master')

@section('title')
    Employee Earning Details
@endsection

@section('content')
    <div class="row">

        <div class="col-12">

            <div class="page-title-box">
                <h4 class="mb-sm-0 font-size-18">
                    Employee Earning Details
                </h4>
            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-lg-12">

            <div class="card">

                <div class="card-header">
                    <h4 class="card-title">
                        Commission Information
                    </h4>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-6">

                            <table class="table table-bordered">

                                <tr>
                                    <th>
                                        Employee Username
                                    </th>
                                    <td>
                                        [ <b>{{ $earning->employee?->username ?? '-' }}</b> ]
                                    </td>
                                </tr>

                                <tr>
                                    <th width="220">
                                        Employee Name
                                    </th>
                                    <td>
                                        {{ $earning->employee?->name ?? '-' }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        Coupon Code
                                    </th>
                                    <td>
                                        {{ $earning->coupon?->code ?? '-' }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        Order Number
                                    </th>
                                    <td>
                                        {{ $earning->order?->order_number ?? '-' }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        Order Delivered At
                                    </th>
                                    <td>
                                        @if ($earning->order?->delivered_at)
                                            {{ \Carbon\Carbon::parse($earning->order->delivered_at)->format('d M Y h:i A') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th width="220">
                                        Created At
                                    </th>
                                    <td>
                                        {{ $earning->created_at->format('d M Y h:i A') }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        Updated At
                                    </th>
                                    <td>
                                        {{ $earning->updated_at->format('d M Y h:i A') }}
                                    </td>
                                </tr>

                            </table>

                        </div>

                        <div class="col-md-6">

                            <table class="table table-bordered">

                                <tr>
                                    <th width="220">
                                        Order Amount
                                    </th>
                                    <td>
                                        ₹ {{ number_format($earning->order_amount, 2) }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        Commission %
                                    </th>
                                    <td>
                                        {{ $earning->commission_percentage }}%
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        Commission ₹
                                    </th>
                                    <td>
                                        ₹ {{ number_format($earning->commission_amount, 2) }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        Withdrawal Requested
                                    </th>
                                    <td>
                                        @if ($earning->is_withdraw_requested)
                                            <span class="badge bg-info">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        Withdrawal Requested At
                                    </th>
                                    <td>
                                        {{ $earning->withdraw_requested_at
                                            ? \Carbon\Carbon::parse($earning->withdraw_requested_at)->format('d M Y h:i A')
                                            : '-' }}
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        Status
                                    </th>
                                    <td>
                                        @if ($earning->status == 'delivery_pending')
                                            <span class="badge bg-info">
                                                Delivery Pending
                                            </span>
                                        @elseif($earning->status == 'pending')
                                            <span class="badge bg-warning">
                                                Commission Pending
                                            </span>
                                        @elseif($earning->status == 'paid')
                                            <span class="badge bg-success">
                                                Paid
                                            </span>
                                        @elseif($earning->status == 'cancelled')
                                            <span class="badge bg-danger">
                                                Cancelled
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Paid At</th>
                                    <td>
                                        {{ $earning->paid_at ? $earning->paid_at->format('d M Y h:i A') : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Paid By</th>
                                    <td>
                                        {{ $earning->paidBy?->name ?? '-' }}
                                    </td>
                                </tr>

                                {{-- <tr>
                                    <th>
                                        Created At
                                    </th>
                                    <td>
                                        {{ $earning->created_at->format('d M Y h:i A') }}
                                    </td>
                                </tr> --}}
                            </table>


                        </div>


                    </div>
                    <a href="{{ route('admin.employee_earnings.index') }}"
                        class="btn btn-secondary justify-content-end mb-3">
                        Back
                    </a>

                </div>

            </div>

        </div>

    </div>
@endsection
