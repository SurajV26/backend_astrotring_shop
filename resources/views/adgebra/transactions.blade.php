@extends('adgebra.layout')
@section('title', 'Transactions')
@section('page_heading', 'Attributed transactions')
@section('page_subheading', 'Order-wise conversion details with payment and fulfilment status.')
@section('content')
    <div class="panel">
        <div class="panelhead">
            <div>
                <h2>Transaction report</h2><span>Customer personal information is intentionally excluded.</span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn dark"
                    href="{{ route('adgebra.export.transactions.excel', request()->query()) }}">Download Excel</a><a
                    class="btn" href="{{ route('adgebra.export.transactions', request()->query()) }}">Download CSV</a>
            </div>
        </div>
        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Order number</th>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Medium</th>
                        <th>Landing page</th>
                        <th>Campaign ID</th>
                        <th>Placement ID</th>
                        <th>Creative ID</th>
                        <th>Click ID</th>
                        <th>Products</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Payment status</th>
                        <th>Order status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $row)
                        <tr>
                            <td><strong>{{ $row->order_number }}</strong></td>
                            <td>{{ \Carbon\Carbon::parse($row->order_date)->format('d M Y, h:i A') }}</td>
                            <td>{{ $row->source ?: '—' }}</td>
                            <td>{{ $row->medium ?: '—' }}</td>
                            <td>{{ ucwords(str_replace('-', ' ', $row->landing_page)) }}</td>
                            <td>{{ $row->campaign_id ?: '—' }}</td>
                            <td>{{ $row->placement_id ?: '—' }}</td>
                            <td>{{ $row->creative_id ?: '—' }}</td>
                            <td>{{ $row->click_id ?: '—' }}</td>
                            <td style="white-space:normal;min-width:180px">{{ $row->products ?: '—' }}</td>
                            <td class="money">₹{{ number_format((float) $row->total_amount, 2) }}</td>
                            <td>{{ $row->payment_method ?: 'COD' }}</td>
                            <td><span class="pill">{{ $row->payment_status ?: 'pending' }}</span></td>
                            <td><span class="pill">{{ $row->order_status }}</span></td>
                        </tr>
                    @empty<tr>
                            <td colspan="14" class="empty">No attributed transactions found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $transactions->links() }}</div>
    </div>
@endsection
