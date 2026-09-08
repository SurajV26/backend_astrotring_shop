<div id="order-view-wrapper">

    @php
        $user = $order->user;

        /*
        |--------------------------------------------------------------------------
        | ORDER STATUS BADGE
        |--------------------------------------------------------------------------
        */
        $statusBadge = match ($order->status) {
            'pending' => 'warning',
            'paid' => 'info',
            'packed' => 'primary',
            'shipped' => 'dark',
            'delivered' => 'success',
            'cancelled' => 'danger',
            'rto' => 'dark text-warning',
            default => 'secondary',
        };

        /*
        |--------------------------------------------------------------------------
        | SHIPPING STATUS BADGE
        |--------------------------------------------------------------------------
        */
        $shippingStatusBadge = match ($order->shipping_status) {
            'pending' => 'warning',
            'paid' => 'info',
            'packed' => 'primary',
            'shipped' => 'dark',
            'delivered' => 'success',
            'cancelled' => 'danger',
            'rto' => 'dark text-warning',
            default => 'secondary',
        };

        /*
        |--------------------------------------------------------------------------
        | STATUS OPTIONS
        |--------------------------------------------------------------------------
        */
        $statusOptions = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'packed' => 'Packed',
            'shipped' => 'Shipped',
            'rto' => 'RTO',
            'cancelled' => 'Cancelled',
        ];
    @endphp


    {{-- =====================================================================
        MODAL HEADER
    ====================================================================== --}}
    <div class="modal-header border-0 pb-0">

        <div class="w-100">

            <div class="d-flex justify-content-between align-items-start flex-wrap gap-4">

                {{-- HEADER LEFT --}}
                <div class="flex-grow-1">

                    <div class="d-flex align-items-center gap-3 flex-wrap">

                        <h4 class="fw-bold mb-0">
                            Order #{{ $order->order_number }}
                        </h4>

                        {{-- AWB / SEND MAIL --}}
                        <form method="POST" action="{{ route('admin.orders.send-mail', $order->id) }}"
                            class="d-flex align-items-center gap-2 flex-wrap">
                            @csrf

                            <input type="text" name="awb_code" class="form-control form-control-sm"
                                style="width: 250px;" value="{{ $order->awb_code ?? '' }}" placeholder="Enter AWB Code"
                                required>

                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-envelope me-1"></i>
                                Send Mail
                            </button>

                        </form>

                    </div>


                    {{-- PLACED DATE --}}
                    <div class="text-muted small mt-2">

                        <i class="far fa-calendar-alt me-1"></i>

                        Placed on
                        {{ optional($order->created_at)->format('d M Y h:i A') ?? 'N/A' }}

                    </div>


                    {{-- PDF ACTIONS --}}
                    @if ($order->pdf)
                        <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">

                            <a href="{{ asset('storage/' . $order->pdf) }}" target="_blank"
                                class="btn btn-danger btn-sm">
                                <i class="fas fa-file-pdf me-1"></i>
                                View PDF
                            </a>

                            <a href="{{ asset('storage/' . $order->pdf) }}" download class="btn btn-success btn-sm">
                                <i class="fas fa-download me-1"></i>
                                Download PDF
                            </a>

                        </div>
                    @endif

                </div>


                {{-- HEADER RIGHT --}}
                <div class="text-end">

                    <span class="badge bg-{{ $statusBadge }} px-3 py-2 fs-6">
                        {{ strtoupper($order->status ?? 'pending') }}
                    </span>

                    <div class="fw-bold text-success fs-4 mt-2">
                        ₹ {{ number_format($order->total_amount ?? 0, 2) }}
                    </div>

                </div>

            </div>

        </div>


        {{-- CLOSE --}}
        <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Close"></button>

    </div>


    {{-- =====================================================================
        MODAL BODY
    ====================================================================== --}}
    <div class="modal-body pt-4">


        {{-- =================================================================
            ORDER STATUS UPDATE
        ================================================================== --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                    <div>

                        <h5 class="fw-bold mb-1 text-primary">
                            <i class="fas fa-sync-alt me-2"></i>
                            Update Order Status
                        </h5>

                        <small class="text-muted">
                            Order status and shipping status are updated together.
                        </small>

                    </div>

                    <div class="text-end">

                        <small class="text-muted d-block mb-1">
                            Current Status
                        </small>

                        <span class="badge bg-{{ $statusBadge }} px-3 py-2">
                            {{ strtoupper($order->status ?? 'pending') }}
                        </span>

                    </div>

                </div>


                <form method="POST" action="{{ route('admin.orders.update-status', $order->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 align-items-end">

                        <div class="col-md-{{ $order->status === 'cancelled' ? '6' : '9' }}">

                            <div class="form-group">

                                <label for="orderStatus" class="form-label fw-bold">
                                    Order Status :
                                </label>

                                <select name="status" id="orderStatus" class="form-control select2-class"
                                    data-placeholder="Select Order Status" required>

                                    @foreach ($statusOptions as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ $order->status === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>

                        </div>


                        {{-- CANCEL REASON --}}
                        <div class="col-md-3" id="cancelReasonWrapper"
                            style="{{ $order->status === 'cancelled' ? '' : 'display:none;' }}">

                            <div class="form-group">

                                <label for="cancelReason" class="form-label fw-bold">
                                    Cancel Reason :
                                </label>

                                <input type="text" name="cancel_reason" id="cancelReason" class="form-control"
                                    value="{{ $order->cancel_reason ?? '' }}" placeholder="Enter cancellation reason"
                                    maxlength="1000">

                            </div>

                        </div>


                        {{-- UPDATE BUTTON --}}
                        <div class="col-md-3">

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i>
                                Update Status
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        {{-- =================================================================
            TOP INFORMATION CARDS
        ================================================================== --}}
        <div class="row g-4">


            {{-- CUSTOMER --}}
            <div class="col-lg-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <h6 class="fw-bold mb-4 text-primary">
                            <i class="fas fa-user me-1"></i>
                            Customer
                        </h6>


                        <div class="mb-3">

                            <small class="text-muted d-block">
                                Customer ID
                            </small>

                            <strong>
                                {{ $user->code ?? 'N/A' }}
                            </strong>

                        </div>


                        <div class="mb-3">

                            <small class="text-muted d-block">
                                Name
                            </small>

                            <strong>
                                {{ $user->name ?? 'N/A' }}
                            </strong>

                        </div>


                        <div class="mb-3">

                            <small class="text-muted d-block">
                                Email
                            </small>

                            <span class="text-break">
                                {{ $order->email ?? 'N/A' }}
                            </span>

                        </div>


                        <div>

                            <small class="text-muted d-block">
                                Mobile
                            </small>

                            {{ $user->mobile ?? 'N/A' }}

                        </div>

                    </div>

                </div>

            </div>


            {{-- ORDER STATS --}}
            <div class="col-lg-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <h6 class="fw-bold mb-4 text-primary">
                            <i class="fas fa-chart-bar me-1"></i>
                            Order Stats
                        </h6>


                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Total Products
                            </span>

                            <strong>
                                {{ $order->items->count() }}
                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Total Qty
                            </span>

                            <strong>
                                {{ $order->items->sum('quantity') }}
                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Categories
                            </span>

                            <strong>
                                {{ $order->items->pluck('product.category.name')->filter()->unique()->count() }}
                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Coupon
                            </span>

                            <strong>
                                {{ $order->coupon->code ?? 'N/A' }}
                            </strong>

                        </div>


                        <hr>


                        <div class="d-flex justify-content-between">

                            <span>
                                Wallet Used
                            </span>

                            <strong>
                                ₹ {{ number_format($order->wallet_used ?? 0, 2) }}
                            </strong>

                        </div>

                    </div>

                </div>

            </div>


            {{-- SHIPPING --}}
            <div class="col-lg-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <h6 class="fw-bold mb-4 text-primary">
                            <i class="fas fa-truck me-1"></i>
                            Shipping
                        </h6>


                        <div class="mb-3">

                            <small class="text-muted d-block">
                                Shipment ID
                            </small>

                            <strong>
                                {{ $order->shipment_id ?? 'N/A' }}
                            </strong>

                        </div>


                        <div class="mb-3">

                            <small class="text-muted d-block">
                                AWB Code
                            </small>

                            <strong>
                                {{ $order->awb_code ?? 'N/A' }}
                            </strong>

                        </div>


                        <div class="mb-3">

                            <small class="text-muted d-block">
                                Courier
                            </small>

                            {{ $order->courier_name ?? 'N/A' }}

                        </div>


                        <div>

                            <small class="text-muted d-block mb-1">
                                Shipping Status
                            </small>

                            <span class="badge bg-{{ $shippingStatusBadge }}">
                                {{ strtoupper($order->shipping_status ?? 'pending') }}
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            {{-- PAYMENT --}}
            <div class="col-lg-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <h6 class="fw-bold mb-4 text-primary">
                            <i class="fas fa-credit-card me-1"></i>
                            Payment
                        </h6>


                        @if ($order->payment)

                            <div class="mb-3">

                                <small class="text-muted d-block">
                                    Transaction ID
                                </small>

                                <strong class="text-break">
                                    {{ $order->payment->transaction_id ?? 'N/A' }}
                                </strong>

                            </div>


                            <div class="mb-3">

                                <small class="text-muted d-block">
                                    Gateway
                                </small>

                                {{ strtoupper($order->payment->payment_gateway ?? 'N/A') }}

                            </div>


                            <div class="mb-3">

                                <small class="text-muted d-block">
                                    Mode
                                </small>

                                {{ strtoupper($order->payment->payment_mode ?? 'N/A') }}

                            </div>


                            <div class="mb-3">

                                <small class="text-muted d-block">
                                    Paid Amount
                                </small>

                                ₹ {{ number_format($order->payment->amount ?? 0, 2) }}

                            </div>


                            @if ($order->is_cod_advance)
                                <div class="mb-3">

                                    <small class="text-muted d-block">
                                        Advance Paid
                                    </small>

                                    <strong class="text-success">
                                        ₹ {{ number_format($order->advance_paid_amount ?? 0, 2) }}
                                    </strong>

                                </div>


                                <div class="mb-3">

                                    <small class="text-muted d-block">
                                        Remaining COD Amount
                                    </small>

                                    <strong class="text-danger">
                                        ₹ {{ number_format($order->remaining_cod_amount ?? 0, 2) }}
                                    </strong>

                                </div>
                            @endif


                            <span class="badge bg-success">
                                {{ strtoupper($order->payment->payment_status ?? 'success') }}
                            </span>
                        @else
                            <div class="text-muted">

                                <i class="fas fa-info-circle me-1"></i>

                                No payment found

                            </div>

                        @endif

                    </div>

                </div>

            </div>

        </div>


        {{-- =================================================================
            ADDRESS + PRICE
        ================================================================== --}}
        <div class="row g-4 mt-1">


            {{-- DELIVERY ADDRESS --}}
            <div class="col-lg-8">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <h6 class="fw-bold mb-4 text-primary">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            Delivery Address
                        </h6>


                        <div class="row">


                            <div class="col-md-6 mb-3">

                                <small class="text-muted d-block">
                                    Name
                                </small>

                                <strong>
                                    {{ $order->name ?? 'N/A' }}
                                </strong>

                            </div>


                            <div class="col-md-6 mb-3">

                                <small class="text-muted d-block">
                                    Mobile
                                </small>

                                {{ $order->mobile ?? 'N/A' }}

                                @if (!empty($order->alternative_mobile))
                                    , {{ $order->alternative_mobile }}
                                @endif

                            </div>


                            <div class="col-md-6 mb-3">

                                <small class="text-muted d-block">
                                    Email
                                </small>

                                <span class="text-break">
                                    {{ $order->email ?? 'N/A' }}
                                </span>

                            </div>


                            <div class="col-md-6 mb-3">

                                <small class="text-muted d-block">
                                    Pincode
                                </small>

                                {{ $order->pincode ?? 'N/A' }}

                            </div>


                            <div class="col-12">

                                <small class="text-muted d-block">
                                    Full Address
                                </small>

                                {{ $order->address ?? 'N/A' }}

                                @if ($order->city)
                                    , {{ $order->city }}
                                @endif

                                @if ($order->state)
                                    , {{ $order->state }}
                                @endif

                                @if ($order->country)
                                    , {{ $order->country }}
                                @endif

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- PRICE BREAKDOWN --}}
            <div class="col-lg-4">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <h6 class="fw-bold mb-4 text-primary">
                            <i class="fas fa-receipt me-1"></i>
                            Price Breakdown
                        </h6>


                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Subtotal
                            </span>

                            <strong>
                                ₹ {{ number_format($order->subtotal ?? 0, 2) }}
                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-2 text-success">

                            <span>
                                Discount
                            </span>

                            <strong>
                                - ₹ {{ number_format($order->discount ?? 0, 2) }}
                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Delivery Charge
                            </span>

                            <strong>
                                ₹ {{ number_format($order->delivery_charge ?? 0, 2) }}
                            </strong>

                        </div>


                        @if (isset($order->price_breakdown) && isset($order->price_breakdown['cod_charge']))
                            <div class="d-flex justify-content-between mb-2">

                                <span>
                                    COD Charge
                                </span>

                                <strong>
                                    ₹ {{ number_format($order->price_breakdown['cod_charge'], 2) }}
                                </strong>

                            </div>
                        @endif


                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Wallet Used
                            </span>

                            <strong>
                                ₹ {{ number_format($order->wallet_used ?? 0, 2) }}
                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-3">

                            <span>
                                Paid Amount
                            </span>

                            <strong>
                                ₹ {{ number_format($order->paid_amount ?? 0, 2) }}
                            </strong>

                        </div>


                        @if ($order->is_cod_advance)
                            <div class="d-flex justify-content-between mb-2 text-success">

                                <span>
                                    Advance Paid
                                </span>

                                <strong>
                                    ₹ {{ number_format($order->advance_paid_amount ?? 0, 2) }}
                                </strong>

                            </div>


                            <div class="d-flex justify-content-between mb-2 text-danger">

                                <span>
                                    Remaining COD
                                </span>

                                <strong>
                                    ₹ {{ number_format($order->remaining_cod_amount ?? 0, 2) }}
                                </strong>

                            </div>
                        @endif


                        <hr>


                        <div class="d-flex justify-content-between align-items-center fs-5 fw-bold">

                            <span>
                                Total
                            </span>

                            <span class="text-success">
                                ₹ {{ number_format($order->total_amount ?? 0, 2) }}
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =================================================================
            ORDER ITEMS
        ================================================================== --}}
        <div class="card border-0 shadow-sm mt-4">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">

                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="fas fa-box-open me-2"></i>
                        Order Items
                    </h5>

                    <span class="badge bg-primary">
                        {{ $order->items->count() }} Items
                    </span>

                </div>


                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th width="70">
                                    Image
                                </th>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Stock
                                </th>

                                <th>
                                    Qty
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Specs
                                </th>

                                <th>
                                    Review
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse ($order->items as $item)

                                @php
                                    $product = $item->product;

                                    $category = optional($product?->category)->name ?? 'N/A';

                                    $review = $product
                                        ? $product->storeReviews->where('user_id', $order->user_id)->first()
                                        : null;
                                @endphp


                                <tr>


                                    {{-- IMAGE --}}
                                    <td>

                                        @if ($item->product_image)
                                            <img src="{{ asset('storage/product/' . $item->product_image) }}"
                                                width="55" height="55" class="rounded border"
                                                style="object-fit: cover;" alt="{{ $product->name ?? 'Product' }}">
                                        @else
                                            <span class="text-muted small">
                                                No Image
                                            </span>
                                        @endif

                                    </td>


                                    {{-- PRODUCT --}}
                                    <td>

                                        <div class="fw-semibold">
                                            {{ $product->name ?? 'Deleted Product' }}
                                        </div>

                                        <div class="small text-muted">
                                            SKU:
                                            {{ $product->code ?? 'N/A' }}
                                        </div>

                                        <div class="small text-muted">
                                            Ratti:
                                            {{ $item->ratti ?? 'N/A' }}
                                        </div>

                                    </td>


                                    {{-- CATEGORY --}}
                                    <td>

                                        <span class="badge bg-info">
                                            {{ $category }}
                                        </span>

                                    </td>


                                    {{-- STOCK --}}
                                    <td>

                                        @if ($product)
                                            <span
                                                class="badge bg-{{ $product->stock_qty > 0 ? 'success' : 'danger' }}">
                                                {{ $product->stock_qty }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                N/A
                                            </span>
                                        @endif

                                    </td>


                                    {{-- QTY --}}
                                    <td>

                                        <strong>
                                            {{ $item->quantity }}
                                        </strong>

                                    </td>


                                    {{-- PRICE --}}
                                    <td>

                                        ₹ {{ number_format($item->price ?? 0, 2) }}

                                    </td>


                                    {{-- TOTAL --}}
                                    <td>

                                        <strong class="text-success">
                                            ₹ {{ number_format($item->total ?? 0, 2) }}
                                        </strong>

                                    </td>


                                    {{-- SPECS --}}
                                    <td>

                                        <small>

                                            Weight:
                                            {{ $item->weight ?? 0 }}

                                            <br>

                                            {{ $item->length ?? 0 }}
                                            ×
                                            {{ $item->breadth ?? 0 }}
                                            ×
                                            {{ $item->height ?? 0 }}

                                        </small>

                                    </td>


                                    {{-- REVIEW --}}
                                    <td>

                                        @if ($review)
                                            <span class="badge bg-warning text-dark">
                                                ⭐ {{ $review->rating }}/5
                                            </span>


                                            @if ($review->review)
                                                <div class="small text-muted mt-1">
                                                    "{{ $review->review }}"
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted small">
                                                No review
                                            </span>
                                        @endif

                                    </td>


                                </tr>

                            @empty

                                <tr>

                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-box-open me-1"></i>
                                        No order items found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- =================================================================
            ORDER TIMELINE
        ================================================================== --}}
        {{-- @if ($order->created_at || $order->paid_at || $order->delivered_at || $order->cancelled_at)
            <div class="card border-0 shadow-sm mt-4">

                <div class="card-body">

                    <h5 class="fw-bold mb-4 text-primary">
                        <i class="fas fa-history me-2"></i>
                        Order Timeline
                    </h5>


                    <div class="row g-3">

                        <div class="col-md-3">

                            <div class="border rounded p-3 h-100">

                                <div class="small text-muted mb-1">
                                    CREATED
                                </div>

                                <strong>
                                    {{ optional($order->created_at)->format('d M Y h:i A') ?: 'N/A' }}
                                </strong>

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="border rounded p-3 h-100">

                                <div class="small text-muted mb-1">
                                    PAID
                                </div>

                                <strong>
                                    {{ optional($order->paid_at)->format('d M Y h:i A') ?: 'N/A' }}
                                </strong>

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="border rounded p-3 h-100">

                                <div class="small text-muted mb-1">
                                    DELIVERED
                                </div>

                                <strong>
                                    {{ optional($order->delivered_at)->format('d M Y h:i A') ?: 'N/A' }}
                                </strong>

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="border rounded p-3 h-100">

                                <div class="small text-muted mb-1">
                                    CANCELLED
                                </div>

                                <strong>
                                    {{ optional($order->cancelled_at)->format('d M Y h:i A') ?: 'N/A' }}
                                </strong>

                            </div>

                        </div>

                    </div>

                </div>

            </div>
        @endif --}}

    </div>


    {{-- =====================================================================
        MODAL FOOTER
    ====================================================================== --}}
    <div class="modal-footer border-0">

        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>
            Close
        </button>

    </div>


    {{-- =====================================================================
        SELECT2 INITIALIZATION
        Same pattern as your existing coupon modal
    ====================================================================== --}}
    <script>
        $('#order-view-wrapper .select2-class').select2({
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder');
            },
            allowClear: true,
            dropdownParent: $('#order-view-wrapper')
        });

        $('#order-view-wrapper').on('change', '#orderStatus', function() {

            const status = $(this).val();
            const $wrapper = $('#cancelReasonWrapper');
            const $input = $('#cancelReason');

            if (status === 'cancelled') {

                $wrapper.show();
                $input.prop('required', true);

            } else {

                $wrapper.hide();
                $input.prop('required', false);
                $input.val('');

            }
        });
    </script>

</div>
