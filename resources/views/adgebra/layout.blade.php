<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title', 'Users') | AstroTring Adgebra</title>
    <style>
        :root {
            --navy: #111b31;
            --navy2: #192743;
            --gold: #c99a4a;
            --ink: #172033;
            --muted: #667085;
            --line: #e7ebf1;
            --bg: #f5f7fa;
            --white: #fff;
            --green: #147d55;
            --red: #b42318
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: var(--ink);
            background: var(--bg);
            font-size: 14px
        }

        a {
            color: inherit
        }

        .app {
            min-height: 100vh
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 250px;
            background: var(--navy);
            color: #e8edf6;
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
            z-index: 20
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 2px 9px 25px;
            border-bottom: 1px solid #ffffff17
        }

        .brandmark {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #f0dfbd;
            color: #815a20;
            display: grid;
            place-items: center;
            font-size: 20px;
            font-weight: 900
        }

        .brand strong {
            display: block;
            font-size: 15px;
            letter-spacing: .01em
        }

        .brand small {
            display: block;
            color: #a9b5c8;
            font-size: 11px;
            margin-top: 3px
        }

        .navlabel {
            padding: 25px 11px 9px;
            color: #8290a8;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .13em;
            font-weight: 800
        }

        .nav {
            display: grid;
            gap: 6px
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 13px;
            border-radius: 10px;
            color: #c2ccda;
            text-decoration: none;
            font-size: 13px;
            font-weight: 650;
            transition: .15s
        }

        .nav a:hover {
            background: #ffffff0d;
            color: #fff
        }

        .nav a.active {
            background: #ffffff16;
            color: #fff;
            box-shadow: inset 3px 0 0 var(--gold)
        }

        .navicon {
            width: 22px;
            height: 22px;
            border-radius: 7px;
            background: #ffffff12;
            display: grid;
            place-items: center;
            font-size: 12px;
            color: #e9c98e
        }

        .sidefoot {
            margin-top: auto;
            padding: 15px 10px 0;
            border-top: 1px solid #ffffff17;
            color: #8f9db2;
            font-size: 11px;
            line-height: 1.6
        }

        .main {
            margin-left: 250px;
            min-height: 100vh
        }

        .topbar {
            height: 72px;
            background: #fff;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            gap: 16px
        }

        .topmeta {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--muted);
            font-size: 12px
        }

        .live-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #18a66a;
            box-shadow: 0 0 0 4px #e6f6ef
        }

        .logout {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 700;
            color: #344054;
            cursor: pointer
        }

        .logout:hover {
            border-color: #c6ccd6;
            background: #f8fafc
        }

        .content {
            max-width: 1600px;
            margin: 0 auto;
            padding: 28px 30px 40px
        }

        .heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 20px
        }

        .eyebrow {
            color: #a47732;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: .13em;
            margin-bottom: 7px
        }

        .heading h1 {
            font-size: 26px;
            letter-spacing: -.04em;
            margin: 0
        }

        .heading p {
            font-size: 13px;
            color: var(--muted);
            margin: 7px 0 0
        }

        .headerpill {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 9px 12px;
            color: #667085;
            font-size: 11px;
            white-space: nowrap
        }

        .filters {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 13px;
            padding: 15px;
            margin-bottom: 18px;
            box-shadow: 0 2px 7px #10182805
        }

        .filtergrid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 11px
        }

        .field label {
            display: block;
            color: #667085;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .055em;
            margin: 0 0 6px
        }

        .field input,
        .field select {
            width: 100%;
            height: 39px;
            border: 1px solid #dce2ea;
            border-radius: 8px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            font-size: 12px;
            padding: 0 10px;
            min-width: 0;
            outline: none
        }

        .field input:focus,
        .field select:focus {
            border-color: #c99a4a;
            box-shadow: 0 0 0 3px #c99a4a20
        }

        .actions {
            display: flex;
            gap: 7px;
            align-items: end
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 9px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: #344054;
            text-decoration: none;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
            cursor: pointer
        }

        .btn:hover {
            background: #f8fafc
        }

        .btn.primary {
            background: var(--navy);
            border-color: var(--navy);
            color: #fff
        }

        .btn.export {
            background: #f8fbff;
            border-color: #dce7f4;
            color: #214c78
        }

        .btnrow {
            display: flex;
            flex-wrap: wrap;
            gap: 8px
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 13px;
            margin-bottom: 18px
        }

        .stat {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 13px;
            padding: 17px 18px
        }

        .statlabel {
            color: var(--muted);
            font-size: 11px;
            font-weight: 750
        }

        .statvalue {
            font-weight: 850;
            letter-spacing: -.04em;
            font-size: 25px;
            margin-top: 7px;
            font-variant-numeric: tabular-nums
        }

        .stathint {
            font-size: 10px;
            color: #98a2b3;
            margin-top: 4px
        }

        .panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 13px;
            overflow: hidden;
            box-shadow: 0 2px 7px #10182805
        }

        .panelhead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--line)
        }

        .panelhead h2 {
            font-size: 13px;
            margin: 0;
            font-weight: 850
        }

        .panelhead p {
            font-size: 11px;
            color: var(--muted);
            margin: 5px 0 0
        }

        .tablewrap {
            width: 100%;
            overflow: auto
        }

        table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse
        }

        th,
        td {
            text-align: left;
            padding: 12px 13px;
            border-bottom: 1px solid #edf0f4;
            vertical-align: top;
            font-size: 11px
        }

        th {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #667085;
            font-weight: 850;
            background: #fafbfc;
            white-space: nowrap
        }

        tbody tr:hover {
            background: #fcfcfd
        }

        tbody tr:last-child td {
            border-bottom: 0
        }

        td strong {
            font-size: 11px;
            font-weight: 800
        }

        .sub {
            display: block;
            color: #98a2b3;
            font-size: 10px;
            margin-top: 4px
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            max-width: 210px
        }

        .chip {
            display: inline-flex;
            border: 1px solid #e7ebf1;
            background: #f8fafc;
            color: #556174;
            border-radius: 5px;
            padding: 3px 6px;
            font-size: 9px;
            line-height: 1.35
        }

        .status {
            display: inline-flex;
            border-radius: 99px;
            padding: 4px 8px;
            background: #f2f4f7;
            color: #475467;
            font-size: 9px;
            font-weight: 800;
            text-transform: capitalize
        }

        .status.delivered,
        .status.paid {
            background: #eaf7f0;
            color: #147d55
        }

        .status.cancelled,
        .status.rto,
        .status.refunded {
            background: #fff0ef;
            color: #b42318
        }

        .status.pending,
        .status.processing {
            background: #fff7e8;
            color: #946200
        }

        .amount {
            font-variant-numeric: tabular-nums;
            font-weight: 800;
            white-space: nowrap
        }

        .empty {
            padding: 38px 15px;
            text-align: center;
            color: #98a2b3;
            font-size: 12px
        }

        .pagination {
            padding: 14px 17px;
            border-top: 1px solid var(--line);
            font-size: 12px
        }

        .pagination nav {
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .pagination nav div {
            display: flex;
            gap: 7px;
            align-items: center
        }

        .pagination a,
        .pagination span {
            padding: 5px 8px;
            border-radius: 6px;
            text-decoration: none
        }

        .pagination a {
            border: 1px solid var(--line)
        }

        .pagination span[aria-current="page"] span {
            background: var(--navy);
            color: white
        }

        .footnote {
            color: #98a2b3;
            font-size: 10px;
            line-height: 1.5;
            margin-top: 14px
        }

        .mobile-head {
            display: none
        }

        @media(max-width:1250px) {
            .filtergrid {
                grid-template-columns: repeat(3, minmax(0, 1fr))
            }

            .actions {
                grid-column: span 3
            }

            .stats {
                grid-template-columns: repeat(3, minmax(0, 1fr))
            }
        }

        @media(max-width:760px) {
            .sidebar {
                position: sticky;
                top: 0;
                width: 100%;
                height: auto;
                padding: 11px 13px;
                display: block
            }

            .brand {
                padding: 0 4px 10px;
                border: 0
            }

            .brandmark {
                width: 34px;
                height: 34px
            }

            .brand strong {
                font-size: 13px
            }

            .brand small {
                font-size: 10px
            }

            .navlabel,
            .sidefoot {
                display: none
            }

            .nav {
                display: flex;
                overflow-x: auto;
                gap: 6px;
                padding-top: 8px
            }

            .nav a {
                padding: 9px 11px;
                white-space: nowrap;
                font-size: 11px
            }

            .navicon {
                width: 18px;
                height: 18px
            }

            .main {
                margin-left: 0
            }

            .topbar {
                height: 55px;
                padding: 0 15px
            }

            .topmeta {
                font-size: 10px
            }

            .content {
                padding: 19px 13px 28px
            }

            .heading {
                display: block
            }

            .heading h1 {
                font-size: 23px
            }

            .headerpill {
                display: inline-block;
                margin-top: 12px
            }

            .filtergrid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 9px
            }

            .actions {
                grid-column: 1/-1
            }

            .actions .btn {
                flex: 1
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 9px
            }

            .stat {
                padding: 13px
            }

            .statvalue {
                font-size: 22px
            }

            .panelhead {
                align-items: flex-start;
                flex-direction: column
            }

            .btnrow {
                width: 100%
            }

            .btnrow .btn {
                flex: 1
            }

            .tablewrap {
                border-top: 0
            }
        }
    </style>
</head>

<body>
    <div class="app">
        <aside class="sidebar">
            <div class="brand">
                <div class="brandmark">A</div>
                <div><strong>AstroTring</strong><small>Adgebra Partner Portal</small></div>
            </div>
            <div class="navlabel">Store activity</div>
            <nav class="nav" aria-label="Partner navigation">
                <a href="{{ route('adgebra.users', request()->query()) }}"
                    class="{{ request()->routeIs('adgebra.users') ? 'active' : '' }}"><span
                        class="navicon">U</span>Users</a>
                <a href="{{ route('adgebra.carts', request()->query()) }}"
                    class="{{ request()->routeIs('adgebra.carts') ? 'active' : '' }}"><span
                        class="navicon">C</span>Cart</a>
                <a href="{{ route('adgebra.orders', request()->query()) }}"
                    class="{{ request()->routeIs('adgebra.orders') ? 'active' : '' }}"><span
                        class="navicon">O</span>Order</a>
            </nav>
            <div class="sidefoot">Restricted publisher access<br>Only Adgebra-attributed store activity is shown.</div>
        </aside>

        <div class="main">
            <header class="topbar">
                <div class="topmeta"><span class="live-dot"></span> Partner reporting portal <span>·</span> India time
                </div>
                <div class="topmeta">
                    <span>Signed in as <strong>{{ $partner->username ?? 'Partner' }}</strong></span>
                    <form method="POST" action="{{ route('adgebra.logout') }}">@csrf<button class="logout"
                            type="submit">Log out</button></form>
                </div>
            </header>

            <main class="content">
                <div class="heading">
                    <div>
                        <div class="eyebrow">AstroTring / Adgebra</div>
                        <h1>@yield('page_heading', 'Users')</h1>
                        <p>@yield('page_subheading', 'Only customers attributed to approved Adgebra landing-page traffic.')</p>
                    </div>
                    <div class="headerpill">Last 30 days by default · Data refreshes on page load</div>
                </div>

                <form class="filters" method="GET" action="{{ request()->url() }}">
                    <div class="filtergrid">
                        <div class="field"><label for="from">From date</label><input id="from" name="from"
                                type="date" value="{{ $filters['from'] }}"></div>
                        <div class="field"><label for="to">To date</label><input id="to" name="to"
                                type="date" value="{{ $filters['to'] }}"></div>
                        <div class="field"><label for="landing_page">Landing page</label><select id="landing_page"
                                name="landing_page">
                                <option value="">All landing pages</option>
                                @foreach (['dhan-yog' => 'Dhan Yog', 'rashi' => 'Rashi', 'rudraksha' => 'Rudraksha', 'bracelets' => 'Bracelets', 'combos' => 'Combos'] as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['landing_page'] === $value)>
                                        {{ $label }}</option>
                                @endforeach
                            </select></div>
                        <div class="field"><label for="campaign_id">Campaign ID</label><input id="campaign_id"
                                name="campaign_id" value="{{ $filters['campaign_id'] }}" placeholder="Campaign ID">
                        </div>
                        <div class="field"><label for="placement_id">Placement ID</label><input id="placement_id"
                                name="placement_id" value="{{ $filters['placement_id'] }}" placeholder="Placement ID">
                        </div>
                        <div class="field"><label for="creative_id">Creative ID</label><input id="creative_id"
                                name="creative_id" value="{{ $filters['creative_id'] }}" placeholder="Creative ID">
                        </div>
                        <div class="field"><label for="search">Search</label><input id="search" name="search"
                                value="{{ $filters['search'] }}" placeholder="Name, mobile, order, product"></div>
                        <div class="actions"><button class="btn primary" type="submit">Apply filters</button><a
                                class="btn" href="{{ request()->url() }}">Reset</a></div>
                    </div>
                </form>

                @yield('content')
                <div class="footnote">Customer contact details are masked. A customer appears here only after an Adgebra
                    landing-page visit is identified with their authenticated AstroTring account. Orders require a
                    matching Adgebra attribution record.</div>
            </main>
        </div>
    </div>
</body>

</html>
