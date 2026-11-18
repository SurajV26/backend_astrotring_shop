@extends('adgebra.layout')
@section('title', 'Overview')
@section('page_heading', 'Campaign overview')
@section('page_subheading', 'Traffic, landing-page engagement and orders attributed to Adgebra.')
@section('content')
    <div class="note">Attribution uses the latest Adgebra landing-page visit stored in the visitor’s browser for up to 30
        days. Unique visitors represent browser identifiers, not verified individual people. Only visits carrying
        <strong>utm_source=Adgebra</strong> are included.</div>
    <section class="cards">
        <div class="card">
            <div class="label">Total landing visits</div>
            <div class="value">{{ number_format($totalVisits) }}</div>
            <div class="hint">Adgebra landing-page views</div>
        </div>
        <div class="card">
            <div class="label">Unique visitors</div>
            <div class="value">{{ number_format($uniqueVisitors) }}</div>
            <div class="hint">Distinct browser identifiers</div>
        </div>
        <div class="card">
            <div class="label">Shop clicks</div>
            <div class="value">{{ number_format($totalClicks) }}</div>
            <div class="hint">Clicks from landing pages to AstroTring</div>
        </div>
        <div class="card">
            <div class="label">Attributed orders</div>
            <div class="value">{{ number_format($totalOrders) }}</div>
            <div class="hint">Orders linked to tracked visits</div>
        </div>
        <div class="card">
            <div class="label">Conversion rate</div>
            <div class="value">{{ number_format($conversionRate, 2) }}%</div>
            <div class="hint">Orders ÷ unique visitors</div>
        </div>
        <div class="card">
            <div class="label">Attributed order value</div>
            <div class="value">₹{{ number_format($orderValue, 2) }}</div>
            <div class="hint">Current order totals, INR</div>
        </div>
    </section>
    <section class="panel">
        <div class="panelhead">
            <div>
                <h2>Campaign performance</h2><span>Grouped by landing page, campaign, placement and creative</span>
            </div><a class="btn" href="{{ route('adgebra.campaigns', request()->query()) }}">View full report</a>
        </div>
        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Landing page</th>
                        <th>Medium</th>
                        <th>Campaign ID</th>
                        <th>Placement ID</th>
                        <th>Creative ID</th>
                        <th>Visits</th>
                        <th>Unique</th>
                        <th>Clicks</th>
                        <th>Orders</th>
                        <th>Order value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaignRows as $row)
                        <tr>
                            <td><strong>{{ ucwords(str_replace('-', ' ', $row->landing_page)) }}</strong></td>
                            <td>{{ $row->medium ?: '—' }}</td>
                            <td>{{ $row->campaign_id ?: '—' }}</td>
                            <td>{{ $row->placement_id ?: '—' }}</td>
                            <td>{{ $row->creative_id ?: '—' }}</td>
                            <td>{{ number_format($row->visits) }}</td>
                            <td>{{ number_format($row->unique_visitors) }}</td>
                            <td>{{ number_format($row->clicks) }}</td>
                            <td><strong>{{ number_format($row->orders) }}</strong></td>
                            <td class="money">₹{{ number_format((float) $row->order_value, 2) }}</td>
                        </tr>
                    @empty<tr>
                            <td colspan="10" class="empty">No campaign visits found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel">
        <div class="panelhead">
            <div>
                <h2>Recent attributed transactions</h2><span>Latest orders associated with Adgebra tracking</span>
            </div><a class="btn" href="{{ route('adgebra.transactions', request()->query()) }}">View all
                transactions</a>
        </div>
        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Order number</th>
                        <th>Order date</th>
                        <th>Landing page</th>
                        <th>Campaign</th>
                        <th>Placement</th>
                        <th>Products</th>
                        <th>Amount</th>
                        <th>Order status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $row)
                        <tr>
                            <td><strong>{{ $row->order_number }}</strong></td>
                            <td>{{ \Carbon\Carbon::parse($row->order_date)->format('d M Y, h:i A') }}</td>
                            <td>{{ ucwords(str_replace('-', ' ', $row->landing_page)) }}</td>
                            <td>{{ $row->campaign_id ?: '—' }}</td>
                            <td>{{ $row->placement_id ?: '—' }}</td>
                            <td>{{ $row->products ?: '—' }}</td>
                            <td class="money">₹{{ number_format((float) $row->total_amount, 2) }}</td>
                            <td><span class="pill">{{ $row->order_status }}</span></td>
                        </tr>
                    @empty<tr>
                            <td colspan="8" class="empty">No attributed orders found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div style="display:flex;gap:9px;flex-wrap:wrap;margin-bottom:18px"><a class="btn dark"
            href="{{ route('adgebra.export.transactions.excel', request()->query()) }}">Download transactions Excel</a><a
            class="btn" href="{{ route('adgebra.export.transactions', request()->query()) }}">Download transactions
            CSV</a><a class="btn" href="{{ route('adgebra.export.campaigns.excel', request()->query()) }}">Download
            campaign Excel</a><a class="btn" href="{{ route('adgebra.export.campaigns', request()->query()) }}">Download
            campaign CSV</a></div>
@endsection
