@extends('layouts.master')

@section('title')
    Dashboard
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Dashboard</h4>
            <div class="d-flex align-items-center">
                <label class="me-2 fw-bold mb-0 text-nowrap">Date Range:</label>
                <input type="text" id="dashboard_date_range" class="form-control" style="min-width:230px"
                    placeholder="Select Date Range">
            </div>
        </div>
    </div>

    {{-- STATS --}}
    <div class="row g-3 mb-3">
        @foreach ([
            'total_users' => ['Total Users', 'fa-users', 'primary'],
            'online_users' => ['Users Online', 'fa-circle', 'success'],
            'total_orders' => ['Total Orders', 'fa-shopping-cart', 'info'],
            'pending_orders' => ['Pending Orders', 'fa-clock', 'warning'],
            'delivered_orders' => ['Delivered Orders', 'fa-check-circle', 'success'],
            'cancelled_orders' => ['Cancelled Orders', 'fa-times-circle', 'danger'],
            'total_products' => ['Total Products', 'fa-box', 'secondary'],
        ] as $key => $meta)
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-{{ $meta[2] }}-subtle text-{{ $meta[2] }} me-3">
                            <i class="fa {{ $meta[1] }}"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">{{ $meta[0] }}</p>
                            <h5 class="mb-0 fw-bold {{ $key }}">
                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Revenue: split into prepaid vs COD collected vs COD pending --}}
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-dark-subtle text-dark me-3">
                        <i class="fa fa-rupee-sign"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Revenue Collected</p>
                        <h5 class="mb-0 fw-bold" id="revenue_collected_total">
                            <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                        </h5>
                        <p class="text-muted mb-0 small mt-1">
                            Prepaid: <span class="fw-semibold prepaid_collected">-</span><br>
                            COD Collected: <span class="fw-semibold cod_collected">-</span><br>
                            COD Pending: <span class="fw-semibold cod_pending">-</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ORDER STATUS BREAKDOWN --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Order Pipeline</div>
                <div class="card-body">
                    <div id="statusPipeline" class="d-flex flex-wrap align-items-stretch gap-0">
                        <div class="text-center text-muted py-3 w-100">Loading...</div>
                    </div>
                    <hr class="my-3">
                    <div id="rtoCard"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- GRAPHS --}}
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Sales & Revenue Analytics</div>
                <div class="card-body">
                    <div id="growthChartError" class="text-danger small d-none">Failed to load chart data.</div>
                    <canvas id="growthChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">User Growth Analytics</div>
                <div class="card-body">
                    <div id="engagementChartError" class="text-danger small d-none">Failed to load chart data.</div>
                    <canvas id="engagementChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- TOP PRODUCTS + LOW STOCK --}}
    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Top Selling Products</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Qty Sold</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="topProductsBody">
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    Low Stock Products
                    <span class="badge bg-danger-subtle text-danger">&le; 5 units</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Stock Left</th>
                                </tr>
                            </thead>
                            <tbody id="lowStockBody">
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .stat-card {
            transition: transform .15s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .bg-primary-subtle {
            background: rgba(13, 110, 253, .12);
        }

        .bg-success-subtle {
            background: rgba(25, 135, 84, .12);
        }

        .bg-info-subtle {
            background: rgba(13, 202, 240, .12);
        }

        .bg-warning-subtle {
            background: rgba(255, 193, 7, .15);
        }

        .bg-danger-subtle {
            background: rgba(220, 53, 69, .12);
        }

        .bg-secondary-subtle {
            background: rgba(108, 117, 125, .12);
        }

        .bg-dark-subtle {
            background: rgba(33, 37, 41, .1);
        }

        /* PIPELINE */
        .pipeline-step {
            flex: 1 1 0;
            min-width: 110px;
            position: relative;
            text-align: center;
            padding: 12px 8px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-right: 20px;
        }

        .pipeline-step:last-child {
            margin-right: 0;
        }

        .pipeline-step::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -17px;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            border-left: 8px solid #ced4da;
        }

        .pipeline-step:last-child::after {
            display: none;
        }

        .pipeline-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-size: 13px;
        }

        .pipeline-count {
            font-size: 20px;
            font-weight: 700;
            line-height: 1;
        }

        .pipeline-label {
            font-size: 11px;
            color: #6c757d;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .exception-card {
            border: 1px solid;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* =========================
           MOBILE RESPONSIVE
        ========================= */

        @media (max-width: 991px) {

            /* Header */
            .row.mb-3 .col-12 {
                flex-direction: column;
                align-items: stretch !important;
                gap: 12px;
            }

            .row.mb-3 .d-flex.align-items-center {
                width: 100%;
                flex-direction: column;
                align-items: flex-start !important;
                gap: 8px;
            }

            #dashboard_date_range {
                width: 100% !important;
                min-width: 100% !important;
            }

            /* Stat Cards */
            .stat-card .card-body {
                padding: 14px;
            }

            .stat-icon {
                width: 42px;
                height: 42px;
                font-size: 16px;
                margin-right: 12px !important;
            }

            .stat-card h5 {
                font-size: 18px;
            }

            .stat-card p {
                font-size: 12px;
            }

            /* Pipeline */
            #statusPipeline {
                display: flex;
                overflow-x: auto;
                flex-wrap: nowrap !important;
                gap: 12px;
                padding-bottom: 10px;
            }

            .pipeline-step {
                min-width: 140px;
                margin-right: 0;
                flex: 0 0 auto;
            }

            .pipeline-step::after {
                display: none;
            }

            .exception-card {
                max-width: 100% !important;
            }

            /* Tables */
            .table {
                min-width: 500px;
            }

            /* Charts */
            canvas {
                max-height: 280px;
            }
        }


        @media (max-width: 767px) {

            h4 {
                font-size: 20px;
            }

            .card-header {
                font-size: 15px;
            }

            .stat-card h5 {
                font-size: 16px;
            }

            .pipeline-count {
                font-size: 18px;
            }

            .pipeline-label {
                font-size: 10px;
            }

            .card-body {
                padding: 14px;
            }

            .table td,
            .table th {
                white-space: nowrap;
                font-size: 13px;
            }

        }


        @media (max-width: 575px) {

            .col-6 {
                width: 100%;
            }

            .stat-card {
                margin-bottom: 10px;
            }

            .stat-card .card-body {
                padding: 16px;
            }

            .stat-icon {
                width: 45px;
                height: 45px;
            }

            #dashboard_date_range {
                font-size: 14px;
            }

            .pipeline-step {
                min-width: 120px;
            }

            .pipeline-count {
                font-size: 16px;
            }

            .pipeline-icon {
                width: 32px;
                height: 32px;
            }

        }
    </style>
@endsection

@section('script')
    <script>
        let datePickerRanges = {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 15 Days': [moment().subtract(15, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [
                moment().subtract(1, 'month').startOf('month'),
                moment().subtract(1, 'month').endOf('month')
            ],
            'Last 3 Month': [
                moment().subtract(2, 'month').startOf('month'),
                moment().endOf('month')
            ],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
            'Last Year': [
                moment().subtract(1, 'year').startOf('year'),
                moment().subtract(1, 'year').endOf('year')
            ],
        };

        let datePickerLocale = {
            format: 'YYYY-MM-DD',
            applyLabel: 'Apply',
            cancelLabel: 'Cancel',
            customRangeLabel: 'Custom',
        };

        let start = moment().subtract(6, 'days');
        let end = moment();

        $('#dashboard_date_range').daterangepicker({
            startDate: start,
            endDate: end,
            maxDate: moment(),
            opens: 'left',
            showDropdowns: true,
            autoUpdateInput: true,
            linkedCalendars: false,
            ranges: datePickerRanges,
            locale: datePickerLocale
        }, loadGraphs);

        const chartColors = {
            orders: '#0d6efd',
            revenue: '#198754',
            registrations: '#0dcaf0',
            online: '#ffc107'
        };

        const growthChart = new Chart(document.getElementById('growthChart'), {
            type: 'line',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        const engagementChart = new Chart(document.getElementById('engagementChart'), {
            type: 'line',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Registrations'
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Online Users'
                        },
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        function formatCurrency(val) {
            val = Number(val) || 0;
            return '₹' + val.toLocaleString('en-IN', {
                maximumFractionDigits: 0
            });
        }

        function formatNumber(val) {
            return (Number(val) || 0).toLocaleString('en-IN');
        }

        // MAIN LOADER
        function loadGraphs(start, end) {
            loadStats(start, end);
            loadStatusBreakdown(start, end);
            loadTopProducts(start, end);
            loadLowStock();

            const params = {
                start_date: start.format('YYYY-MM-DD'),
                end_date: end.format('YYYY-MM-DD')
            };

            $('#growthChartError').addClass('d-none');
            $.get('{{ route('admin.dashboard.graph.growth') }}', params)
                .done(res => {
                    growthChart.data.labels = res.labels;
                    growthChart.data.datasets = [{
                            label: 'Orders',
                            data: res.orders,
                            borderWidth: 2,
                            borderColor: chartColors.orders,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Revenue',
                            data: res.revenue,
                            borderWidth: 2,
                            borderColor: chartColors.revenue,
                            tension: 0.3,
                            yAxisID: 'y1'
                        }
                    ];
                    growthChart.update();
                })
                .fail(() => $('#growthChartError').removeClass('d-none'));

            $('#engagementChartError').addClass('d-none');
            $.get('{{ route('admin.dashboard.graph.engagement') }}', params)
                .done(res => {
                    engagementChart.data.labels = res.labels;
                    engagementChart.data.datasets = [{
                            label: 'Registrations',
                            data: res.registrations,
                            borderWidth: 2,
                            borderColor: chartColors.registrations,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Online Users',
                            data: res.online_users,
                            borderWidth: 2,
                            borderColor: chartColors.online,
                            tension: 0.3,
                            yAxisID: 'y1'
                        }
                    ];
                    engagementChart.update();
                })
                .fail(() => $('#engagementChartError').removeClass('d-none'));
        }

        // STATS
        function loadStats(start, end) {
            const params = {
                start_date: start.format('YYYY-MM-DD'),
                end_date: end.format('YYYY-MM-DD')
            };

            $.get('{{ route('admin.dashboard.stats') }}', params)
                .done(res => {
                    Object.keys(res).forEach(k => {
                        const isCurrency = (k === 'prepaid_collected' || k === 'cod_collected' || k ===
                            'cod_pending');
                        const val = isCurrency ? formatCurrency(res[k]) : formatNumber(res[k]);
                        $('.' + k).html(val);
                    });

                    const totalCollected = (Number(res.prepaid_collected) || 0) + (Number(res.cod_collected) || 0);
                    $('#revenue_collected_total').html(formatCurrency(totalCollected));
                })
                .fail(() => {
                    $('.total_users, .online_users, .total_orders, .pending_orders, .delivered_orders, .cancelled_orders, .total_products, .prepaid_collected, .cod_collected, .cod_pending')
                        .html('<span class="text-danger small">Error</span>');
                    $('#revenue_collected_total').html('<span class="text-danger small">Error</span>');
                });
        }

        const pipelineOrder = ['pending', 'packed', 'shipped', 'delivered'];
        const pipelineMeta = {
            pending: {
                label: 'Pending',
                icon: 'fa-hourglass-half',
                color: '#6c757d'
            },
            packed: {
                label: 'Packed',
                icon: 'fa-box',
                color: '#0d6efd'
            },
            shipped: {
                label: 'Shipped',
                icon: 'fa-truck',
                color: '#212529'
            },
            delivered: {
                label: 'Delivered',
                icon: 'fa-check-circle',
                color: '#198754'
            },
        };

        // STATUS BREAKDOWN
        function loadStatusBreakdown(start, end) {
            const params = {
                start_date: start.format('YYYY-MM-DD'),
                end_date: end.format('YYYY-MM-DD')
            };

            $.get('{{ route('admin.dashboard.status.breakdown') }}', params)
                .done(res => {
                    const counts = {};
                    res.forEach(item => counts[item.status] = item.count);

                    // Pipeline (main flow)
                    let pipelineHtml = pipelineOrder.map(status => {
                        const meta = pipelineMeta[status];
                        return `
                    <div class="pipeline-step">
                        <div class="pipeline-icon" style="background:${meta.color}22; color:${meta.color}">
                            <i class="fa ${meta.icon}"></i>
                        </div>
                        <div class="pipeline-count" style="color:${meta.color}">${formatNumber(counts[status] || 0)}</div>
                        <div class="pipeline-label">${meta.label}</div>
                    </div>
                `;
                    }).join('');
                    $('#statusPipeline').html(pipelineHtml);

                    // RTO — single compact exception card
                    const rtoColor = '#ffc107';
                    $('#rtoCard').html(`
                <div class="exception-card" style="border-color:${rtoColor}55; background:${rtoColor}11; max-width:260px">
                    <div class="pipeline-icon" style="background:${rtoColor}22; color:${rtoColor}">
                        <i class="fa fa-rotate-left"></i>
                    </div>
                    <div>
                        <div class="pipeline-label mb-1">RTO (Return to Origin)</div>
                        <div class="pipeline-count" style="color:${rtoColor}; font-size:20px">${formatNumber(counts['rto'] || 0)}</div>
                    </div>
                </div>
            `);
                })
                .fail(() => {
                    $('#statusPipeline').html('<div class="text-center text-danger py-3 w-100">Failed to load</div>');
                    $('#rtoCard').html('');
                });
        }

        // TOP PRODUCTS
        function loadTopProducts(start, end) {
            const params = {
                start_date: start.format('YYYY-MM-DD'),
                end_date: end.format('YYYY-MM-DD')
            };

            $.get('{{ route('admin.dashboard.top.products') }}', params)
                .done(res => {
                    if (!res.length) {
                        $('#topProductsBody').html(
                            '<tr><td colspan="3" class="text-center text-muted py-4">No sales in this range</td></tr>'
                        );
                        return;
                    }
                    let rows = res.map(p => `
                <tr>
                    <td>${$('<div>').text(p.product_name).html()}</td>
                    <td class="text-end">${formatNumber(p.total_qty)}</td>
                    <td class="text-end">${formatCurrency(p.revenue)}</td>
                </tr>
            `).join('');
                    $('#topProductsBody').html(rows);
                })
                .fail(() => {
                    $('#topProductsBody').html(
                        '<tr><td colspan="3" class="text-center text-danger py-4">Failed to load</td></tr>');
                });
        }

        // LOW STOCK (not date-dependent)
        function loadLowStock() {
            $.get('{{ route('admin.dashboard.low.stock.products') }}')
                .done(res => {
                    if (!res.length) {
                        $('#lowStockBody').html(
                            '<tr><td colspan="2" class="text-center text-muted py-4">All products well stocked</td></tr>'
                        );
                        return;
                    }
                    let rows = res.map(p => `
                <tr>
                    <td>${$('<div>').text(p.name).html()}</td>
                    <td class="text-end"><span class="badge ${p.stock_qty == 0 ? 'bg-danger' : 'bg-warning text-dark'}">${p.stock_qty}</span></td>
                </tr>
            `).join('');
                    $('#lowStockBody').html(rows);
                })
                .fail(() => {
                    $('#lowStockBody').html(
                        '<tr><td colspan="2" class="text-center text-danger py-4">Failed to load</td></tr>');
                });
        }

        // INITIAL LOAD
        loadGraphs(start, end);
    </script>
@endsection
