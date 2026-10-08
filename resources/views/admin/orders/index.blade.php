@extends('layouts.master')

@section('title')
    Orders
@endsection

@section('content')
    {{-- PAGE TITLE --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">

                <h4 class="mb-sm-0 font-size-18">
                    Orders
                </h4>

                <div class="page-title-right">
                    <button type="button" id="export-orders-btn" class="btn btn-soft-success waves-effect waves-light">

                        <i class="fas fa-file-excel"></i>
                        Export Excel

                    </button>
                </div>

            </div>
        </div>
    </div>


    {{-- FILTER SECTION --}}
    <div class="row">
        <div class="col-12">

            <div class="card">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <h4 class="card-title mb-0">
                        Filter
                    </h4>

                    <button type="button" id="reset-filter-btn" class="btn btn-light waves-effect waves-light">

                        <i class="fa fa-undo"></i>
                        Reset

                    </button>

                </div>


                <div class="card-body">

                    <div class="row">

                        {{-- USER --}}
                        <div class="col">

                            <label class="form-label fw-bold">
                                User
                            </label>

                            <select id="user_id" class="form-control select2-class2" data-placeholder="Select User">

                                <option value=""></option>

                                @foreach (\App\Models\User::where('role_id', 3)->orderBy('name')->get() as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->code }} - {{ $user->name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- CATEGORY --}}
                        <div class="col">

                            <label class="form-label fw-bold">
                                Category
                            </label>

                            <select id="category_id" class="form-control select2-class2" data-placeholder="Select Category">

                                <option value=""></option>

                                @foreach (\App\Models\Category::orderBy('name')->get() as $cat)
                                    <option value="{{ $cat->id }}">
                                        {{ $cat->name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- PRODUCT --}}
                        <div class="col">

                            <label class="form-label fw-bold">
                                Product
                            </label>

                            <select id="product_id" class="form-control select2-class2" data-placeholder="Select Product">

                                <option value=""></option>

                                @foreach (\App\Models\Product::orderBy('name')->get() as $p)
                                    <option value="{{ $p->id }}">
                                        {{ $p->name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- ORDER STATUS --}}
                        <div class="col">

                            <label class="form-label fw-bold">
                                Status
                            </label>

                            <select id="status" class="form-control select2-class2" data-placeholder="Select Status">

                                <option value=""></option>

                                <option value="pending">
                                    Cod Pending
                                </option>

                                <option value="paid">
                                    Prepaid Paid Pending
                                </option>

                                <option value="packed">
                                    Packed
                                </option>

                                <option value="shipped">
                                    Shipped
                                </option>

                                <option value="delivered">
                                    Delivered
                                </option>

                                <option value="rto">
                                    RTO
                                </option>

                                <option value="cancelled">
                                    Cancelled
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>


    {{-- ORDERS TABLE --}}
    <div class="row">

        <div class="col-12">

            <div class="card border">

                <div class="card-body">

                    <table id="orders-table" class="table table-bordered dt-responsive nowrap w-100">

                        <thead>

                            <tr>

                                <th>
                                    Order No
                                </th>

                                <th>
                                    User
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Products
                                    <div class="text-muted small">
                                        (Items)
                                    </div>
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody></tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- ORDER EXPORT MODAL --}}
    {{-- ========================================================= --}}

    <div class="modal fade" id="exportOrderModal" tabindex="-1" aria-labelledby="exportOrderModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                {{-- MODAL HEADER --}}
                <div class="modal-header">

                    <h5 class="modal-title" id="exportOrderModalLabel">

                        <i class="fas fa-file-excel text-success me-1"></i>

                        Export Orders

                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>

                </div>


                {{-- MODAL BODY --}}
                <div class="modal-body">

                    {{-- DATE RANGE --}}
                    <div class="form-group mb-3">

                        <label class="form-label fw-bold">
                            Select Date Range
                        </label>

                        <input type="text" id="export_order_date_range" class="form-control"
                            placeholder="Choose Date Range" autocomplete="off">

                        <input type="hidden" id="export_order_from_date">

                        <input type="hidden" id="export_order_to_date">

                        <small class="text-muted">
                            Date filter will be applied using
                            <strong>orders.created_at</strong>.
                        </small>

                    </div>


                    {{-- STATUS --}}
                    <div class="form-group">

                        <label class="form-label fw-bold">
                            Select Status
                        </label>

                        <select id="export_order_status" class="form-control">

                            <option value="">
                                All Status
                            </option>

                            <option value="pending">
                                Cod Pending
                            </option>

                            <option value="paid">
                                Prepaid Paid Pending
                            </option>

                            <option value="packed">
                                Packed
                            </option>

                            <option value="shipped">
                                Shipped
                            </option>

                            <option value="delivered">
                                Delivered
                            </option>

                            <option value="rto">
                                RTO
                            </option>

                            <option value="cancelled">
                                Cancelled
                            </option>

                        </select>

                    </div>

                </div>


                {{-- MODAL FOOTER --}}
                <div class="modal-footer">

                    <button type="button" class="btn btn-light waves-effect" data-bs-dismiss="modal">

                        Cancel

                    </button>


                    <button type="button" id="confirm-order-export-btn" class="btn btn-success waves-effect">

                        <i class="fas fa-file-excel"></i>

                        Export Excel

                    </button>

                </div>

            </div>

        </div>

    </div>
@endsection


@section('script')
    <script type="text/javascript">
        $(document).ready(function() {


            /* =========================================================
             | ORDERS DATATABLE
             |========================================================= */

            const table = $('#orders-table').DataTable({

                processing: true,

                serverSide: true,


                ajax: {

                    url: '{{ route('admin.orders.list') }}',

                    data: function(d) {

                        d.user_id =
                            $('#user_id').val();

                        d.category_id =
                            $('#category_id').val();

                        d.product_id =
                            $('#product_id').val();

                        d.status =
                            $('#status').val();

                    }

                },


                columns: [

                    /* ORDER NUMBER */
                    {
                        data: 'order_no',
                        name: 'order_number'
                    },


                    /* USER */
                    {
                        data: 'user',
                        name: 'user'
                    },


                    /* CATEGORY */
                    {
                        data: 'category',
                        name: 'category'
                    },


                    /* PRODUCTS */
                    {
                        data: 'products',
                        name: 'products',
                        orderable: false,
                        searchable: false
                    },


                    /* AMOUNT */
                    {
                        data: 'amount',
                        name: 'total_amount'
                    },


                    /* STATUS */
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },


                    /* DATE */
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },


                    /* ACTION */
                    {
                        data: null,

                        className: 'text-center',

                        orderable: false,

                        searchable: false,

                        render: function(row) {

                            return `

                                <a href="javascript:void(0);"

                                   data-href="{{ route('admin.orders.view') }}/${row.id}"

                                   class="btn btn-soft-success btn-sm waves-effect waves-light open-remote-modal"

                                   data-target="#xxlRemoteModal">

                                    <i class="mdi mdi-eye font-size-16"></i>

                                </a>

                            `;

                        }

                    }

                ]

            });


            /* =========================================================
             | NORMAL FILTER CHANGE
             |========================================================= */

            $('#user_id, #category_id, #product_id, #status')
                .on('change', function() {

                    table.ajax.reload();

                });


            /* =========================================================
             | RESET FILTER
             |========================================================= */

            $('#reset-filter-btn').on('click', function() {

                $('#user_id')
                    .val('')
                    .trigger('change');


                $('#category_id')
                    .val('')
                    .trigger('change');


                $('#product_id')
                    .val('')
                    .trigger('change');


                $('#status')
                    .val('')
                    .trigger('change');


                table.ajax.reload();

            });


            /* =========================================================
             | ORDER EXPORT DATE PICKER
             |========================================================= */

            $('#export_order_date_range').daterangepicker({

                autoUpdateInput: false,

                opens: 'left',

                locale: {

                    format: 'DD-MM-YYYY',

                    separator: ' - ',

                    applyLabel: 'Apply',

                    cancelLabel: 'Clear',

                    customRangeLabel: 'Custom'

                },


                ranges: {

                    /* TODAY */
                    'Today': [

                        moment(),

                        moment()

                    ],


                    /* YESTERDAY */
                    'Yesterday': [

                        moment().subtract(1, 'days'),

                        moment().subtract(1, 'days')

                    ],


                    /* LAST 7 DAYS */
                    'Last 7 Days': [

                        moment().subtract(6, 'days'),

                        moment()

                    ],


                    /* LAST 15 DAYS */
                    'Last 15 Days': [

                        moment().subtract(14, 'days'),

                        moment()

                    ],


                    /* LAST MONTH */
                    'Last Month': [

                        moment()
                        .subtract(1, 'month')
                        .startOf('month'),

                        moment()
                        .subtract(1, 'month')
                        .endOf('month')

                    ],


                    /* THIS MONTH */
                    'This Month': [

                        moment().startOf('month'),

                        moment().endOf('month')

                    ],


                    /* THIS YEAR */
                    'This Year': [

                        moment().startOf('year'),

                        moment().endOf('year')

                    ],


                    /* LAST YEAR */
                    'Last Year': [

                        moment()
                        .subtract(1, 'year')
                        .startOf('year'),

                        moment()
                        .subtract(1, 'year')
                        .endOf('year')

                    ]

                }

            });


            /* =========================================================
             | EXPORT DATE APPLY
             |========================================================= */

            $('#export_order_date_range').on(
                'apply.daterangepicker',
                function(ev, picker) {

                    const fromDate =
                        picker.startDate.format('YYYY-MM-DD');

                    const toDate =
                        picker.endDate.format('YYYY-MM-DD');


                    $(this).val(

                        picker.startDate.format('DD-MM-YYYY') +

                        ' - ' +

                        picker.endDate.format('DD-MM-YYYY')

                    );


                    $('#export_order_from_date')
                        .val(fromDate);


                    $('#export_order_to_date')
                        .val(toDate);

                }
            );


            /* =========================================================
             | EXPORT DATE CLEAR
             |========================================================= */

            $('#export_order_date_range').on(
                'cancel.daterangepicker',
                function() {

                    $(this).val('');

                    $('#export_order_from_date')
                        .val('');

                    $('#export_order_to_date')
                        .val('');

                }
            );


            /* =========================================================
             | OPEN EXPORT MODAL
             |========================================================= */

            $('#export-orders-btn').on('click', function() {

                /*
                 * Clear previous export dates
                 */
                $('#export_order_date_range')
                    .val('');

                $('#export_order_from_date')
                    .val('');

                $('#export_order_to_date')
                    .val('');


                /*
                 * Copy currently selected table status
                 *
                 * Example:
                 * Current table status = shipped
                 * Export modal status = shipped
                 */
                const currentStatus =
                    $('#status').val();


                $('#export_order_status')
                    .val(currentStatus);


                /*
                 * Open modal
                 */
                $('#exportOrderModal').modal('show');

            });


            /* =========================================================
             | CONFIRM EXPORT
             |========================================================= */

            $('#confirm-order-export-btn').on(
                'click',
                function() {

                    const button = $(this);


                    /*
                     * Selected dates
                     */
                    const fromDate =
                        $('#export_order_from_date').val();

                    const toDate =
                        $('#export_order_to_date').val();


                    /*
                     * Selected status
                     */
                    const status =
                        $('#export_order_status').val();


                    /*
                     * Validate date range
                     */
                    if (!fromDate || !toDate) {

                        alert(
                            'Please select a date range first.'
                        );

                        return;

                    }


                    /*
                     * Export route
                     */
                    let url =
                        `{{ route('admin.orders.export') }}`;


                    /*
                     * URL params
                     */
                    const params =
                        new URLSearchParams();


                    /* DATE */

                    params.append(
                        'from_date',
                        fromDate
                    );

                    params.append(
                        'to_date',
                        toDate
                    );


                    /* STATUS */

                    if (status !== '') {

                        params.append(
                            'status',
                            status
                        );

                    }


                    /*
                     * EXISTING TABLE FILTERS
                     *
                     * These are also passed to export,
                     * so export can exactly match
                     * the current filters.
                     */

                    const userId =
                        $('#user_id').val();

                    const categoryId =
                        $('#category_id').val();

                    const productId =
                        $('#product_id').val();


                    /* USER */

                    if (userId) {

                        params.append(
                            'user_id',
                            userId
                        );

                    }


                    /* CATEGORY */

                    if (categoryId) {

                        params.append(
                            'category_id',
                            categoryId
                        );

                    }


                    /* PRODUCT */

                    if (productId) {

                        params.append(
                            'product_id',
                            productId
                        );

                    }


                    /*
                     * Final URL
                     */
                    url +=
                        '?' +
                        params.toString();


                    /*
                     * Disable button
                     */
                    button.prop(
                        'disabled',
                        true
                    );


                    /*
                     * Loading state
                     */
                    button.html(
                        '<i class="fas fa-spinner fa-spin"></i> Exporting...'
                    );


                    /*
                     * Start Excel download
                     */
                    window.location.href = url;


                    /*
                     * Reset UI
                     */
                    setTimeout(function() {

                        $('#exportOrderModal')
                            .modal('hide');


                        button.prop(
                            'disabled',
                            false
                        );


                        button.html(
                            '<i class="fas fa-file-excel"></i> Export Excel'
                        );

                    }, 1500);

                }
            );

        });
    </script>
@endsection
