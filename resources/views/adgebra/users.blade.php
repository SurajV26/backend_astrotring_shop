@extends('adgebra.layout')
@section('title', 'Users')
@section('page_heading', 'Users')
@section('page_subheading', 'Customers who arrived through an Adgebra landing page and then logged in to AstroTring.')
@section('content')
    <div class="stats">
        <div class="stat">
            <div class="statlabel">Adgebra users identified</div>
            <div class="statvalue">{{ number_format($totalUsers) }}</div>
            <div class="stathint">Unique logged-in customer accounts</div>
        </div>
        <div class="stat">
            <div class="statlabel">Identified landing visits</div>
            <div class="statvalue">{{ number_format($totalVisits) }}</div>
            <div class="stathint">Adgebra visits linked to an account</div>
        </div>
        <div class="stat">
            <div class="statlabel">Current filter</div>
            <div class="statvalue" style="font-size:17px">{{ $filters['from'] }} <span style="color:#98a2b3">to</span>
                {{ $filters['to'] }}</div>
            <div class="stathint">Date range applied to first account identification</div>
        </div>
    </div>
    <section class="panel">
        <div class="panelhead">
            <div>
                <h2>Logged-in Adgebra customers</h2>
                <p>One row per AstroTring user, even when the same user has multiple Adgebra visits.</p>
            </div>
            <div class="btnrow"><a class="btn export"
                    href="{{ route('adgebra.export.users.xlsx', request()->query()) }}">Download Excel</a><a class="btn"
                    href="{{ route('adgebra.export.users.csv', request()->query()) }}">Download CSV</a></div>
        </div>
        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Contact (masked)</th>
                        <th>Registered</th>
                        <th>First Adgebra login</th>
                        <th>Latest identification</th>
                        <th>Landing pages</th>
                        <th>Campaign IDs</th>
                        <th>Placement IDs</th>
                        <th>Visits</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $row)
                        <tr>
                            <td><strong>{{ $row->customer_name ?: 'Customer' }}</strong><span class="sub">User
                                    #{{ $row->user_id }}</span></td>
                            <td>{{ $row->mobile_masked }}<span class="sub">{{ $row->email_masked }}</span></td>
                            <td>{{ $row->registered_at ? \Carbon\Carbon::parse($row->registered_at)->format('d M Y') : '—' }}
                            </td>
                            <td>{{ $row->first_identified_at ? \Carbon\Carbon::parse($row->first_identified_at)->format('d M Y, h:i A') : '—' }}
                            </td>
                            <td>{{ $row->last_identified_at ? \Carbon\Carbon::parse($row->last_identified_at)->format('d M Y, h:i A') : '—' }}
                            </td>
                            <td>
                                <div class="chips">
                                    @foreach (explode(', ', (string) $row->landing_pages) as $page)
                                        <span class="chip">{{ ucwords(str_replace('-', ' ', $page)) }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>{{ $row->campaign_ids ?: '—' }}</td>
                            <td>{{ $row->placement_ids ?: '—' }}</td>
                            <td><strong>{{ number_format((int) $row->linked_visits) }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty">No Adgebra-attributed users found for these filters. A user
                                appears after login when the store frontend identifies their saved Adgebra visit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="pagination">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
