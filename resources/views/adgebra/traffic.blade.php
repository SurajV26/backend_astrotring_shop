@extends('adgebra.layout')
@section('title', 'Traffic')
@section('page_heading', 'Adgebra traffic overview')
@section('page_subheading',
    'Recorded landing-page visits and shop-link clicks, including visitors who have not logged in.')
@push('styles')
    <style>
        .traffic-report { font-size: 15px; line-height: 1.65; }
        .traffic-report .stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .traffic-report .statlabel { font-size: 14px; line-height: 1.5; }
        .traffic-report .stathint { font-size: 13px; line-height: 1.6; color: #667085; }
        .traffic-report .panel + .panel { margin-top: 20px; }
        .traffic-report .panelhead h2 { font-size: 18px; }
        .traffic-report .panelhead p { font-size: 14px; white-space: normal; line-height: 1.6; }
        .traffic-report th { font-size: 13px; text-transform: none; letter-spacing: 0; }
        .traffic-report td { font-size: 14px; }
        .traffic-notice { padding: 16px 20px; margin-bottom: 20px; border: 1px solid #cadbf0; border-left: 4px solid #3565a2; border-radius: 12px; background: #f0f6ff; }
        .traffic-notice p { margin: 5px 0 0; color: #475467; }
        .traffic-body { padding: 20px; }
        .traffic-definitions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; margin: 0; }
        .traffic-definitions dt { font-weight: 750; margin-bottom: 6px; }
        .traffic-definitions dd { margin: 0; color: #475467; }
        .traffic-note { padding: 14px 20px; margin: 0; background: #f7f9fc; border-top: 1px solid var(--line); font-size: 14px; color: #475467; }
        .traffic-report details { padding: 15px 0; border-bottom: 1px solid var(--line); }
        .traffic-report details:last-child { border-bottom: 0; }
        .traffic-report summary { cursor: pointer; font-weight: 700; }
        .traffic-report details p { margin: 10px 0 0; color: #475467; }
        .traffic-report code { overflow-wrap: anywhere; }
        @media(max-width:1100px) { .traffic-report .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media(max-width:600px) { .traffic-report .stats, .traffic-definitions { grid-template-columns: 1fr; } }
    </style>
@endpush
@section('content')
<div class="traffic-report">
    <div class="stats">
        <div class="stat">
            <div class="statlabel">Landing-page visits</div>
            <div class="statvalue">{{ number_format($totals->visits) }}</div>
            <div class="stathint">Recorded arrivals from Adgebra; repeat visits included</div>
        </div>
        <div class="stat">
            <div class="statlabel">Unique landing browsers</div>
            <div class="statvalue">{{ number_format($totals->visitors) }}</div>
            <div class="stathint">Distinct browser IDs across all selected pages</div>
        </div>
        <div class="stat">
            <div class="statlabel">Unique shop-link clickers</div>
            <div class="statvalue">{{ number_format($totals->shop_visitors) }}</div>
            <div class="stathint">Unique browsers that clicked an astrotring.shop link</div>
        </div>
        <div class="stat">
            <div class="statlabel">Total shop-link clicks</div>
            <div class="statvalue">{{ number_format($totals->shop_clicks) }}</div>
            <div class="stathint">Repeat clicks included</div>
        </div>
    </div>
    <section class="panel">
        <div class="panelhead">
            <div>
                <h2>Traffic by landing page</h2>
                <p>{{ $filters['from'] }} to {{ $filters['to'] }} &middot; {{ config('app.timezone') }}.
                    Only recorded Adgebra-tagged traffic is included.</p>
            </div>
        </div>
        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Landing page</th>
                        <th>Visits</th>
                        <th>Unique landing browsers</th>
                        <th>Unique shop-link clickers</th>
                        <th>Total shop-link clicks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $row)
                        <tr>
                            <td><strong>{{ ucwords(str_replace('-', ' ', $row->landing_page)) }}</strong></td>
                            <td>{{ number_format($row->visits) }}</td>
                            <td>{{ number_format($row->visitors) }}</td>
                            <td>{{ number_format($row->shop_visitors) }}</td>
                            <td>{{ number_format($row->shop_clicks) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">No Adgebra landing-page visits recorded for these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- <p class="traffic-note">A browser may visit several landing pages. Page-level unique counts can add up to more than the overall unique total, which counts each browser ID once.</p> --}}
    </section>
</div>
@endsection
