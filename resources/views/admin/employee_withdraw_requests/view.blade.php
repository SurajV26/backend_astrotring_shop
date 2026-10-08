@extends('layouts.master')

@section('title')
    Withdraw Request Details
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="mb-sm-0 font-size-18">
                    Withdraw Request Details
                </h4>
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-lg-12">

            <div class="card">

                <div class="card-header">
                    <h4 class="card-title">
                        Withdrawal Information
                    </h4>
                </div>

                <div class="card-body">

                    <table class="table table-bordered">

                        <tr>
                            <th width="250">
                                Employee Username
                            </th>
                            <td>
                                [ <b>{{ $request->employee?->username ?? '-' }}</b> ]
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Employee Name
                            </th>
                            <td>
                                {{ $request->employee?->name ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Withdrawal Amount
                            </th>
                            <td>
                                ₹ {{ number_format($request->amount, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Status
                            </th>
                            <td>
                                @if ($request->status == 'pending')
                                    <span class="badge bg-warning">
                                        Pending
                                    </span>
                                @elseif($request->status == 'approved')
                                    <span class="badge bg-success">
                                        Approved
                                    </span>
                                @elseif($request->status == 'rejected')
                                    <span class="badge bg-danger">
                                        Rejected
                                    </span>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Requested At
                            </th>
                            <td>
                                {{ $request->requested_at ? \Carbon\Carbon::parse($request->requested_at)->format('d M Y h:i A') : '-' }}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Processed At
                            </th>
                            <td>
                                {{ $request->processed_at ? \Carbon\Carbon::parse($request->processed_at)->format('d M Y h:i A') : '-' }}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Processed By
                            </th>
                            <td>
                                {{ $request->processedBy?->name ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Created At
                            </th>
                            <td>
                                {{ $request->created_at->format('d M Y h:i A') }}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Updated At
                            </th>
                            <td>
                                {{ $request->updated_at->format('d M Y h:i A') }}
                            </td>
                        </tr>

                    </table>

                    @if (auth()->user()->type != 'employee' && $request->status == 'pending')
                        <form method="POST" action="{{ route('admin.employee_withdraw_requests.approve', $request->id) }}"
                            style="display:inline-block">

                            @csrf

                            <button type="submit" class="btn btn-success">
                                Approve
                            </button>

                        </form>

                        <form method="POST" action="{{ route('admin.employee_withdraw_requests.reject', $request->id) }}"
                            style="display:inline-block">

                            @csrf

                            <button type="submit" class="btn btn-danger">
                                Reject
                            </button>

                        </form>
                    @endif

                    <a href="{{ route('admin.employee_withdraw_requests.index') }}" class="btn btn-secondary">
                        Back
                    </a>

                </div>

            </div>

        </div>

    </div>
@endsection
