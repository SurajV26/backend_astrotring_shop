@extends('adgebra.layout')
@section('title', 'Cart')
@section('page_heading', 'Cart')
@section('page_subheading', 'Saved cart items belonging only to customers identified from Adgebra landing-page
    traffic.')
@section('content')
    <div class="stats">
        <div class="stat">
            <div class="statlabel">Active attributed carts</div>
            <div class="statvalue">{{ number_format($totalCarts) }}</div>
            <div class="stathint">Logged-in Adgebra customers with saved cart items</div>
        </div>
        <div class="stat">
            <div class="statlabel">Current cart value</div>
            <div class="statvalue">₹{{ number_format($cartValue, 2) }}</div>
            <div class="stathint">Sum of saved cart-item totals (INR)</div>
        </div>
        <div class="stat">
            <div class="statlabel">Date filter</div>
            <div class="statvalue" style="font-size:17px">{{ $filters['from'] }} <span style="color:#98a2b3">to</span>
                {{ $filters['to'] }}</div>
            <div class="stathint">Date range applied to cart-item activity</div>
        </div>
    </div>
    <section class="panel">
        <div class="panelhead">
            <div>
                <h2>Saved / abandoned cart items</h2>
                <p>Only current cart_items are shown. After checkout clears a cart, its purchase appears in Order instead.
                </p>
            </div>
            <div class="btnrow"><a class="btn export"
                    href="{{ route('adgebra.export.carts.xlsx', request()->query()) }}">Download Excel</a><a class="btn"
                    href="{{ route('adgebra.export.carts.csv', request()->query()) }}">Download CSV</a></div>
        </div>
        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact (masked)</th>
                        <th>Cart ID</th>
                        <th>Saved products</th>
                        <th>Lines / Qty</th>
                        <th>Cart value</th>
                        <th>Last cart activity</th>
                        <th>Landing page</th>
                        <th>Campaign</th>
                        <th>Placement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($carts as $row)
                        <tr>
                            <td><strong>{{ $row->customer_name ?: 'Customer' }}</strong><span class="sub">User
                                    #{{ $row->user_id }}</span></td>
                            <td>{{ $row->mobile_masked }}<span class="sub">{{ $row->email_masked }}</span></td>
                            <td>#{{ $row->cart_id }}</td>
                            <td style="white-space:normal;min-width:220px;max-width:310px">{{ $row->products ?: '—' }}</td>
                            <td>{{ (int) $row->product_lines }} lines<span class="sub">{{ (int) $row->total_quantity }}
                                    items</span></td>
                            <td class="amount">₹{{ number_format((float) $row->cart_value, 2) }}</td>
                            <td>{{ $row->last_cart_activity ? \Carbon\Carbon::parse($row->last_cart_activity)->format('d M Y, h:i A') : '—' }}
                            </td>
                            <td>{{ $row->landing_pages ?: '—' }}</td>
                            <td>{{ $row->campaign_ids ?: '—' }}</td>
                            <td>{{ $row->placement_ids ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty">No saved cart items from identified Adgebra users were found
                                for these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($carts->hasPages())
            <div class="pagination">{{ $carts->links() }}</div>
        @endif
    </section>
@endsection
