@extends('adgebra.layout')
@section('title', 'Order')
@section('page_heading', 'Order')
@section('page_subheading', 'Orders placed by authenticated customers whose orders are linked to an Adgebra landing-page
    visit.')
@section('content')
    <div class="stats">
        <div class="stat">
            <div class="statlabel">Attributed orders</div>
            <div class="statvalue">{{ number_format($totalOrders) }}</div>
            <div class="stathint">Orders matching the Adgebra visit and customer account</div>
        </div>
        <div class="stat">
            <div class="statlabel">Order value</div>
            <div class="statvalue">₹{{ number_format($orderValue, 2) }}</div>
            <div class="stathint">Recorded order total; includes order-level charges</div>
        </div>
        <div class="stat">
            <div class="statlabel">Date filter</div>
            <div class="statvalue" style="font-size:17px">{{ $filters['from'] }} <span style="color:#98a2b3">to</span>
                {{ $filters['to'] }}</div>
            <div class="stathint">Date range applied to order date</div>
        </div>
    </div>
    <section class="panel">
        <div class="panelhead">
            <div>
                <h2>Attributed store orders</h2>
                <p>Order and order_items are joined through the Adgebra attribution record and the same user_id.</p>
            </div>
            <div class="btnrow"><a class="btn export"
                    href="{{ route('adgebra.export.orders.xlsx', request()->query()) }}">Download Excel</a><a class="btn"
                    href="{{ route('adgebra.export.orders.csv', request()->query()) }}">Download CSV</a></div>
        </div>
        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Contact (masked)</th>
                        <th>Products</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Order status</th>
                        <th>Landing page</th>
                        <th>Campaign / Placement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $row)
                        <tr>
                            <td><strong>{{ $row->order_number ?: '#' . $row->order_id }}</strong><span class="sub">Order
                                    #{{ $row->order_id }}</span></td>
                            <td>{{ $row->order_date ? \Carbon\Carbon::parse($row->order_date)->format('d M Y, h:i A') : '—' }}
                            </td>
                            <td><strong>{{ $row->customer_name ?: 'Customer' }}</strong><span class="sub">User
                                    #{{ $row->user_id }}</span></td>
                            <td>{{ $row->mobile_masked }}<span class="sub">{{ $row->email_masked }}</span></td>
                            <td style="white-space:normal;min-width:220px;max-width:310px">{{ $row->products ?: '—' }}<span
                                    class="sub">Qty: {{ (int) ($row->total_quantity ?? 0) }}</span></td>
                            <td class="amount">₹{{ number_format((float) $row->total_amount, 2) }}</td>
                            <td>{{ $row->payment_method ?: '—' }}<span
                                    class="sub">{{ $row->payment_status ?: '—' }}</span></td>
                            <td><span
                                    class="status {{ strtolower(str_replace(' ', '-', (string) $row->order_status)) }}">{{ $row->order_status ?: '—' }}</span>
                            </td>
                            <td>{{ ucwords(str_replace('-', ' ', (string) $row->landing_page)) }}</td>
                            <td>{{ $row->campaign_id ?: '—' }}<span class="sub">{{ $row->placement_id ?: '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty">No attributed orders found for these filters. An order appears
                                here only when its user_id and Adgebra visit are matched in the attribution table.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="pagination">{{ $orders->links() }}</div>
        @endif
    </section>
@endsection
