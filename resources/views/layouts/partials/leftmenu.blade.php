@php
$user = auth()->user();
@endphp

<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">

                {{-- ================= ADMIN ================= --}}
                @if($user && $user->isSuperAdmin())

                <li>
                    <a href="{{ route('admin.dashboard.index') }}">
                        <i class="bx bx-home-circle"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.product_stocks.index') }}">
                        <i class="bx bx-package"></i>
                        <span>Product Stock</span>
                    </a>
                </li>

                <li class="menu-title">PERMISSIONS & EMPLOYEES</li>
                <li>
                    <a href="{{ route('admin.employees.index') }}">
                        <i class="bx bx-user-circle"></i>
                        <span>Employees</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.permissions.index') }}">
                        <i class="bx bx-lock-alt"></i>
                        <span>Permission</span>
                    </a>
                </li>

                <li class="menu-title">USERS</li>
                <li>
                    <a href="{{ route('admin.users.index') }}">
                        <i class="bx bx-user"></i>
                        <span>Users</span>
                    </a>
                </li>
                
                <li class="menu-title">DELIVERY RATES</li>

                <li>
                    <a href="{{ route('admin.delivery_rates.index') }}">
                        <i class="bx bxs-truck"></i>
                        <span>Delivery Rates</span>
                    </a>
                </li>

                <li class="menu-title">Coupons & PRODUCTS</li>

                <li>
                    <a href="{{ route('admin.coupons.index') }}">
                        <i class="bx bxs-discount"></i>
                        <span>Coupons</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.product_categories.index') }}">
                        <i class="bx bx-box"></i>
                        <span>Product Category</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.products.index') }}">
                        <i class="bx bx-collection"></i>
                        <span>Products</span>
                    </a>
                </li>

                <li class="menu-title">ORDERS & RETURNS</li>

                <li>
                    <a href="{{ route('admin.orders.index') }}">
                        <i class="bx bx-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.order_delivery.index') }}">
                        <i class="bx bx-store"></i>
                        <span>Order Delivery</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.employee_earnings.index') }}">
                        <i class="bx bx-rupee"></i>
                        <span>Employee Earnings</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.employee_withdraw_requests.index') }}">
                        <i class="bx bx-money"></i>
                        <span>Withdraw Requests</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.returns.index') }}">
                        <i class="bx bx-undo"></i>
                        <span>Returns</span>
                    </a>
                </li>

                <li class="menu-title">BANNERS</li>

                <li>
                    <a href="{{ route('admin.store_banners.index') }}">
                        <i class="bx bx-images"></i>
                        <span>Store Banner</span>
                    </a>
                </li>
                
                <li class="menu-title">POLICIES</li>

                <li>
                    <a href="{{ route('admin.policy_categories.index') }}">
                        <i class="bx bx-layer"></i>
                        <span>Policy Category</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.policies.index') }}">
                        <i class="bx bx-list-ul"></i>
                        <span>Policy</span>
                    </a>
                </li>

                {{-- ================= EMPLOYEE ================= --}}
                @elseif($user)

                    @if($user->hasAccess('dashboard'))
                    <li>
                        <a href="{{ route('admin.dashboard.index') }}">
                            <i class="bx bx-home-circle"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    @endif

                    @if($user->hasAccess('earned'))
                    <li>
                        <a href="{{ route('admin.dashboard.index') }}">
                            <i class="bx bx-rupee"></i>
                            <span>Earned</span>
                        </a>
                    </li>
                    @endif

                    @if($user->hasAccess('product_stocks'))
                    <li>
                        <a href="{{ route('admin.product_stocks.index') }}">
                            <i class="bx bx-package"></i>
                            <span>Product Stock</span>
                        </a>
                    </li>
                    @endif

                    {{-- USERS --}}
                    @if($user->hasAccess('astrologers') || $user->hasAccess('users') || $user->hasAccess('payouts'))
                    <!--<li class="menu-title">Astrologers & Users</li>-->
                    @endif

                    @if($user->hasAccess('users'))
                    <li>
                        <a href="{{ route('admin.users.index') }}">
                            <i class="bx bx-group"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    @endif

                    
                    @if($user->hasAccess('delivery_rates'))
                    <li class="menu-title">DELIVERY RATES</li>

                    <li>
                        <a href="{{ route('admin.delivery_rates.index') }}">
                            <i class="bx bxs-truck"></i>
                            <span>Delivery Rates</span>
                        </a>
                    </li>
                    @endif

                    {{-- PRODUCTS --}}
                    @if($user->hasAccess('coupons') || $user->hasAccess('product_categories') ||
                    $user->hasAccess('products'))
                    <li class="menu-title">Coupons & Products</li>
                    @endif

                    @if($user->hasAccess('coupons'))
                    <li>
                        <a href="{{ route('admin.coupons.index') }}">
                            <i class="bx bxs-discount"></i>
                            <span>Coupon</span>
                        </a>
                    </li>
                    @endif

                    @if($user->hasAccess('product_categories'))
                    <li>
                        <a href="{{ route('admin.product_categories.index') }}">
                            <i class="bx bx-box"></i>
                            <span>Product Category</span>
                        </a>
                    </li>
                    @endif

                    @if($user->hasAccess('products'))
                    <li>
                        <a href="{{ route('admin.products.index') }}">
                            <i class="bx bx-collection"></i>
                            <span>Product</span>
                        </a>
                    </li>
                    @endif

                    {{-- ORDERS --}}
                    @if($user->hasAccess('orders') || $user->hasAccess('returns') || $user->hasAccess('employee_earnings'))
                    <li class="menu-title">ORDERS & RETURNS</li>
                    @endif

                    @if($user->hasAccess('orders'))
                    <li>
                        <a href="{{ route('admin.orders.index') }}">
                            <i class="bx bx-cart"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    @endif

                    @if($user->hasAccess('employee_earnings'))
                    <li>
                        <a href="{{ route('admin.employee_earnings.index') }}">
                            <i class="bx bx-rupee"></i>
                            <span>My Earnings</span>
                        </a>
                    </li>
                    @endif

                    @if($user->hasAccess('returns'))
                    <li>
                        <a href="{{ route('admin.returns.index') }}">
                            <i class="bx bx-undo"></i>
                            <span>Returns</span>
                        </a>
                    </li>
                    @endif

                    {{-- BANNERS --}}
                    @if($user->hasAccess('astro_banners') || $user->hasAccess('store_banners') ||
                    $user->hasAccess('send_mail'))
                    <li class="menu-title">Banners</li>
                    @endif

                    @if($user->hasAccess('store_banners'))
                    <li>
                        <a href="{{ route('admin.store_banners.index') }}">
                            <i class="bx bx-store"></i>
                            <span>Store Banner</span>
                        </a>
                    </li>
                    @endif
                    
                    {{-- POLICIES --}}
                    @if ($user->hasAccess('policy_categories') || $user->hasAccess('policies'))
                        <li class="menu-title">Policy</li>
                    @endif

                    @if ($user->hasAccess('policy_categories'))
                        <li>
                            <a href="{{ route('admin.policy_categories.index') }}">
                                <i class="bx bx-layer"></i>
                                <span>Policy Category</span>
                            </a>
                        </li>
                    @endif

                    @if ($user->hasAccess('policies'))
                        <li>
                            <a href="{{ route('admin.policies.index') }}">
                                <i class="bx bx-list-ul"></i>
                                <span>Policy</span>
                            </a>
                        </li>
                    @endif
                    
                @endif

            </ul>
        </div>
    </div>
</div>