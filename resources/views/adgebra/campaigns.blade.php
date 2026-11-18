@extends('adgebra.layout')
@section('title', 'Campaign reports')
@section('page_heading', 'Campaign reports')
@section('page_subheading', 'Compare campaign, placement, creative and landing-page performance.')
@section('content')
    <div class="panel">
        <div class="panelhead">
            <div>
                <h2>Detailed campaign performance</h2><span>Visits are grouped by the tracking parameters captured on the
                    landing page.</span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn dark"
                    href="{{ route('adgebra.export.campaigns.excel', request()->query()) }}">Download Excel</a><a
                    class="btn" href="{{ route('adgebra.export.campaigns', request()->query()) }}">Download CSV</a></div>
        </div>
        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Landing page</th>
                        <th>Source</th>
                        <th>Medium</th>
                        <th>Campaign ID</th>
                        <th>Placement ID</th>
                        <th>Creative ID</th>
                        <th>Visits</th>
                        <th>Unique visitors</th>
                        <th>Shop clicks</th>
                        <th>Orders</th>
                        <th>Conversion</th>
                        <th>Order value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaignRows as $row)
                        @php($rate = (int) $row->unique_visitors > 0 ? round(((int) $row->orders / (int) $row->unique_visitors) * 100, 2) : 0)
                        <tr>
                            <td><strong>{{ ucwords(str_replace('-', ' ', $row->landing_page)) }}</strong></td>
                            <td>{{ $row->source ?: '—' }}</td>
                            <td>{{ $row->medium ?: '—' }}</td>
                            <td>{{ $row->campaign_id ?: '—' }}</td>
                            <td>{{ $row->placement_id ?: '—' }}</td>
                            <td>{{ $row->creative_id ?: '—' }}</td>
                            <td>{{ number_format($row->visits) }}</td>
                            <td>{{ number_format($row->unique_visitors) }}</td>
                            <td>{{ number_format($row->clicks) }}</td>
                            <td><strong>{{ number_format($row->orders) }}</strong></td>
                            <td>{{ number_format($rate, 2) }}%</td>
                            <td class="money">₹{{ number_format((float) $row->order_value, 2) }}</td>
                        </tr>
                    @empty<tr>
                            <td colspan="12" class="empty">No campaign data found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($campaignRows, 'links'))
            <div class="pagination">{{ $campaignRows->links() }}</div>
        @endif
    </div>
@endsection
