<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">

            <div class="navbar-brand-box">
                <a href="{{ route('admin.dashboard.index') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ url('assets/images/favicon.png') }}" alt="" height="32" />
                    </span>
                    <span class="logo-lg">
                        <img src="{{ url('assets/images/logo-light.png') }}" alt="" height="36" />
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>
        </div>

        <div class="d-flex">
            <div class="dropdown d-none d-lg-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" data-bs-toggle="fullscreen">
                    <i class="bx bx-fullscreen"></i>
                </button>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{-- <img class="rounded-circle header-profile-user" src="{{ url("storage/user") . '/' . ($admin->profile_image ?? 'avatar.png') }}"
                    alt="" /> --}}
                    <img class="rounded-circle header-profile-user"
                        src="{{ Auth::user()->profile_image  ? asset('storage/user/' . Auth::user()->profile_image)  : 'https://placehold.co/32x32' }}"
                        alt="" />
                    <span class="d-none d-xl-inline-block ms-1" key="t-henry">{{ Auth::user()->name }}</span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    {{-- <a class="dropdown-item" href="{{ route('admin.profile.details') }}"><i
                        class="bx bx-user font-size-16 align-middle me-1"></i> <span key="t-profile">Profile</span></a>
                    --}}

                    <a class="dropdown-item" href="{{ route('admin.profile.details') }}">
                        <i class="bx bx-user font-size-16 align-middle me-1"></i>
                        <span key="t-profile">Profile</span>
                    </a>

                    {{-- @if (Can::is_accessible('system_settings'))
                    <a class="dropdown-item d-block" href="{{ route('admin.settings.index') }}"><i
                            class="bx bx-wrench font-size-16 align-middle me-1"></i> <span
                            key="t-settings">Settings</span></a>
                    @endif --}}

                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="{{ route('admin.profile.logout') }}"><i
                            class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> <span
                            key="t-logout">Logout</span></a>
                </div>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon right-bar-toggle waves-effect">
                    <i class="bx bx-cog bx-spin"></i>
                </button>
            </div>
        </div>
    </div>
</header>