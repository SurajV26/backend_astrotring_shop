@php
    $priceBreakdown = is_array($order->price_breakdown)
        ? $order->price_breakdown
        : json_decode($order->price_breakdown ?? '{}', true) ?? [];

    $couponDiscount = round((float) ($order->discount ?? ($priceBreakdown['coupon_discount'] ?? 0)), 2);

    $subtotal = round((float) ($order->subtotal ?? ($priceBreakdown['subtotal'] ?? 0)), 2);

    $deliveryCharge = round((float) ($order->delivery_charge ?? ($priceBreakdown['delivery_charge'] ?? 0)), 2);

    $orderTaxableAmount = round((float) ($order->taxable_amount ?? ($priceBreakdown['taxable_amount'] ?? 0)), 2);
    $orderGstRate = round((float) ($order->gst_rate ?? ($priceBreakdown['gst_rate'] ?? 0)), 2);
    $orderCgstAmount = round((float) ($order->cgst_amount ?? ($priceBreakdown['cgst_amount'] ?? 0)), 2);
    $orderSgstAmount = round((float) ($order->sgst_amount ?? ($priceBreakdown['sgst_amount'] ?? 0)), 2);
    $orderIgstAmount = round((float) ($order->igst_amount ?? ($priceBreakdown['igst_amount'] ?? 0)), 2);
    $orderTaxType = $order->tax_type ?? ($priceBreakdown['tax_type'] ?? null);
    $totalGstAmount = round($orderCgstAmount + $orderSgstAmount + $orderIgstAmount, 2);

    $shippingGstRate = round((float) ($priceBreakdown['shipping_gst_rate'] ?? 18), 2);
    $shippingTaxableAmount = round((float) ($priceBreakdown['shipping_taxable_amount'] ?? 0), 2);
    $shippingGstAmount = round((float) ($priceBreakdown['shipping_gst_amount'] ?? 0), 2);

    $codCharge = round((float) ($priceBreakdown['cod_charge'] ?? 0), 2);
    $codTaxableAmount = round((float) ($priceBreakdown['cod_taxable_amount'] ?? 0), 2);
    $codGstRate = round((float) ($priceBreakdown['cod_gst_rate'] ?? 18), 2);
    $codGstAmount = round((float) ($priceBreakdown['cod_gst_amount'] ?? 0), 2);
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        * {
            box-sizing: border-box;
        }

        /*
         * DejaVu Sans does not contain all Devanagari glyphs.
         * Keep the normal invoice font for English/numbers and use
         * Noto Sans Devanagari for product/customer text that may
         * contain Hindi. The TTF must exist at storage/fonts/.
         */
        @font-face {
            font-family: 'NotoDevanagari';
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/NotoSansDevanagari-Regular.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'NotoDevanagari';
            font-style: normal;
            font-weight: bold;
            src: url('{{ storage_path('fonts/NotoSansDevanagari-Bold.ttf') }}') format('truetype');
        }

        @page {
            size: A4 portrait;
            margin: 30px 30px 78px 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        /* HEADER */

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .header-table .logo-cell img {
            height: 40px;
        }

        .title {
            text-align: right;
        }

        .title h2 {
            margin: 0;
            font-size: 16px;
        }

        .title p {
            margin: 2px 0 0;
            font-size: 11px;
            color: #666;
        }

        /* SOLD BY / ADDRESS */

        .top-table td {
            vertical-align: top;
            padding: 0;
            font-size: 11px;
            line-height: 1.6;
        }

        .top-table .right-col {
            text-align: right;
        }

        .top-table strong {
            display: block;
            margin-bottom: 3px;
        }

        .top-table,
        .meta-table {
            font-family: 'NotoDevanagari', DejaVu Sans, sans-serif;
        }

        /* META */

        .meta-table td {
            font-size: 11px;
            padding: 4px 0;
            vertical-align: top;
        }

        .meta-table .right-col {
            text-align: right;
        }

        /* ITEMS */

        table.items {
            margin-top: 15px;
            font-size: 11px;
        }

        table.items th,
        table.items td {
            border: 1px solid #999;
            padding: 6px 8px;
            text-align: center;
        }

        table.items th {
            background: #f2f2f2;
        }

        table.items td.desc {
            text-align: left;
            font-family: 'NotoDevanagari', DejaVu Sans, sans-serif;
            line-height: 1.45;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.items td.desc small {
            color: #666;
            display: block;
            font-family: 'NotoDevanagari', DejaVu Sans, sans-serif;
            line-height: 1.35;
        }

        .total-row td {
            font-weight: bold;
            text-align: right !important;
            background: #f2f2f2;
        }

        /* INFO BOX */

        table.info-box {
            border: 1px solid #999;
            border-top: none;
        }

        table.info-box td {
            padding: 12px;
            font-size: 11px;
            vertical-align: top;
        }

        table.info-box .sign-col {
            width: 40%;
            text-align: right;
            vertical-align: top;
            font-weight: bold;
            white-space: nowrap;
        }

        table.info-box .sign-col img.signature {
            height: 40px;
            width: auto;
            margin: 8px 0;
        }

        .footer-note {
            border: 1px solid #999;
            border-top: none;
            padding: 6px 8px;
            font-size: 11px;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }

        .payment-table td {
            border: 1px solid #999;
            padding: 8px;
            font-size: 11px;
            vertical-align: top;
        }

        .payment-table strong {
            display: block;
            margin-bottom: 3px;
        }

        /* Keep small invoice conclusion blocks intact, but allow the document
         * itself to flow naturally onto additional pages. */
        .info-box,
        .footer-note {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}

    <table class="header-table">
        <tr>
            <td class="logo-cell" width="50%">
                <img src="{{ public_path('assets/images/logo-dark.png') }}" alt="Logo">
            </td>

            <td class="title" width="50%">
                <h2>Tax Invoice/Bill of Supply</h2>
                <p>(Original for Recipient)</p>
            </td>
        </tr>
    </table>

    <br>

    {{-- SOLD BY / BILLING / SHIPPING --}}

    @php
        // Check if shipping address is same as billing address
        $sameAddress =
            !$order->addressData ||
            ($order->addressData->name === $order->name &&
                $order->addressData->address === $order->address &&
                $order->addressData->city === $order->city &&
                $order->addressData->pincode === $order->pincode);
    @endphp

    <table class="top-table">
        <tr>

            <td width="50%">
                <strong>Sold By :</strong>

                Veltex Services Private Limited,<br>
                711, Plot A09, ITL Tower, Netaji Subhash Place,<br>
                Pitampura, Delhi 110034<br>
                Email: care@astrotring.shop<br><br>

                <strong style="display:inline;">PAN No:</strong>
                AAGCK6574Q <br>
                <strong style="display:inline;">GST Registration No:</strong>
                07AAGCK6574Q1ZO
            </td>

            <td width="50%" class="right-col">

                <strong>
                    {{ $sameAddress ? 'Billing & Shipping Address :' : 'Billing Address :' }}
                </strong>

                {{ $order->name }},<br>
                {{ $order->email }}, {{ $order->address }},<br>
                {{ $order->city }}, {{ $order->state }}, {{ $order->country }} - {{ $order->pincode }},<br>

                Mob - {{ $order->mobile }}
                {{ $order->alternative_mobile ? ', ' . $order->alternative_mobile : '' }}

                <br><br>

                {{-- State/UT Code --}}

                <strong style="margin-top:8px;">State/UT Code: {{ $order->state_code }}</strong>


                @if (!$sameAddress)
                    <br>

                    <strong>Shipping Address :</strong>

                    {{ $order->addressData->name }},<br>
                    {{ $order->addressData->email }},
                    {{ $order->addressData->address }},<br>

                    {{ $order->addressData->city }},
                    {{ $order->addressData->state }},
                    {{ $order->addressData->country }} -
                    {{ $order->addressData->pincode }},<br>

                    Mob - {{ $order->addressData->mobile }}<br>

                    {{-- State/UT Code --}}
                    {{--
                    <strong>State/UT Code:</strong>
                    {{ $order->addressData->state_code }}<br>
                    --}}
                @endif

                <strong>
                    Place of delivery & supply:
                </strong>
                {{ $order->city }}, {{ $order->state }}, {{ $order->country }} - {{ $order->pincode }}

            </td>
        </tr>
    </table>

    <br>

    {{-- ORDER META --}}

    <table class="meta-table">
        <tr>

            <td width="50%">
                <strong>Order Number:</strong>
                {{ $order->order_number }}<br>

                <strong>Order Date:</strong>
                {{ $order->created_at->format('d/m/Y') }}
            </td>

            <td width="50%" class="right-col">
                <strong>Invoice Number :</strong>
                {{ $order->invoice_number }}<br>

                <strong>Invoice Date :</strong>
                {{ ($order->created_at ?? now())->format('d/m/Y') }}
            </td>

        </tr>
    </table>

    {{-- ITEMS TABLE --}}

    <table class="items">

        <thead>
            <tr>
                <th>S.No</th>
                <th style="text-align:left;">Description</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Taxable Amount</th>
                <th>Tax Rate</th>
                <th>Tax Type</th>
                <th>Tax Amount</th>
                <th>Total Amount</th>
            </tr>
        </thead>

        <tbody>

            {{-- PRODUCTS --}}

            @foreach ($order->items as $index => $item)
                <tr>

                    <td>
                        {{ $index + 1 }}
                    </td>

                    <td class="desc">
                        {{ $item->product_name }}

                        <small>
                            HSN: {{ $item->hsn_code ?: '-' }}
                        </small>
                    </td>

                    <td>
                        ₹{{ number_format((float) $item->price, 2) }}
                    </td>

                    <td>
                        {{ (int) $item->quantity }}
                    </td>

                    <td>
                        ₹{{ number_format((float) $item->taxable_amount, 2) }}
                    </td>

                    <td>
                        {{ number_format((float) $item->gst_rate, 2) }}%
                    </td>

                    <td>
                        @if ($item->tax_type === 'cgst_sgst')
                            CGST + SGST
                        @elseif ($item->tax_type === 'igst')
                            IGST
                        @else
                            -
                        @endif
                    </td>

                    <td>
                        ₹{{ number_format((float) $item->gst_amount, 2) }}
                    </td>

                    <td>
                        ₹{{ number_format((float) $item->total, 2) }}
                    </td>

                </tr>
            @endforeach

            {{-- SUBTOTAL --}}

            @if ($subtotal > 0)
                <tr>
                    <td></td>
                    <td class="desc">
                        Subtotal
                    </td>
                    <td>-</td>
                    <td>-</td>
                    <td>
                        ₹{{ number_format((float) $order->items->sum('taxable_amount'), 2) }}
                    </td>
                    <td>-</td>
                    <td>-</td>
                    <td>
                        ₹{{ number_format((float) $order->items->sum('gst_amount'), 2) }}
                    </td>
                    <td>
                        ₹{{ number_format($subtotal, 2) }}
                    </td>
                </tr>
            @endif

            {{-- DELIVERY CHARGE --}}

            @if ($deliveryCharge > 0)
                <tr>

                    <td></td>

                    <td class="desc">
                        Delivery Charge
                    </td>

                    <td>
                        ₹{{ number_format($deliveryCharge, 2) }}
                    </td>

                    <td>
                        1
                    </td>

                    <td>
                        ₹{{ number_format($shippingTaxableAmount, 2) }}
                    </td>

                    <td>
                        {{ number_format($shippingGstRate, 2) }}%
                    </td>

                    <td>
                        @if ($orderTaxType === 'cgst_sgst')
                            CGST + SGST
                        @elseif ($orderTaxType === 'igst')
                            IGST
                        @else
                            -
                        @endif
                    </td>

                    <td>
                        ₹{{ number_format($shippingGstAmount, 2) }}
                    </td>

                    <td>
                        ₹{{ number_format($deliveryCharge, 2) }}
                    </td>

                </tr>
            @endif

            {{-- COUPON DISCOUNT --}}

            @if ($couponDiscount > 0)
                <tr>

                    <td></td>

                    <td class="desc">

                        Coupon Discount

                        @if ($order->coupon)
                            <small>
                                Coupon:
                                {{ $order->coupon->code ?? 'Applied Coupon' }}
                            </small>
                        @endif

                    </td>

                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>

                    <td>
                        -₹{{ number_format($couponDiscount, 2) }}
                    </td>

                </tr>
            @endif

            {{-- COD CHARGE --}}

            @if ($codCharge > 0)
                <tr>

                    <td></td>

                    <td class="desc">
                        COD Charge
                    </td>

                    <td>
                        ₹{{ number_format($codCharge, 2) }}
                    </td>

                    <td>
                        1
                    </td>

                    <td>
                        ₹{{ number_format($codTaxableAmount, 2) }}
                    </td>

                    <td>
                        {{ number_format($codGstRate, 2) }}%
                    </td>

                    <td>
                        @if ($orderTaxType === 'cgst_sgst')
                            CGST + SGST
                        @elseif ($orderTaxType === 'igst')
                            IGST
                        @else
                            -
                        @endif
                    </td>

                    <td>
                        ₹{{ number_format($codGstAmount, 2) }}
                    </td>

                    <td>
                        ₹{{ number_format($codCharge, 2) }}
                    </td>

                </tr>
            @endif

            {{-- TOTAL --}}

            <tr class="total-row">

                <td colspan="8">
                    TOTAL:
                </td>

                <td>
                    ₹{{ number_format((float) $order->total_amount, 2) }}
                </td>

            </tr>

        </tbody>

    </table>

    {{-- TAX BREAKUP --}}

    <div style="margin-top: 8px; font-size: 10px; text-align: right;">
        <strong>Tax Breakup:</strong>
        @if ($orderTaxType === 'cgst_sgst')
            CGST ₹{{ number_format($orderCgstAmount, 2) }}
            &nbsp;|&nbsp;
            SGST ₹{{ number_format($orderSgstAmount, 2) }}
        @elseif ($orderTaxType === 'igst')
            IGST ₹{{ number_format($orderIgstAmount, 2) }}
        @endif
        &nbsp;|&nbsp;
        Total GST ₹{{ number_format($totalGstAmount, 2) }}
    </div>

    {{-- AMOUNT IN WORDS + SIGNATORY --}}

    @php
        $amountInWords = \App\Helpers\NumberHelper::convertToWords($order->total_amount);

        // NumberHelper currently returns "Dollars Only" in this project.
        // This invoice is in INR, so normalize only the wording here.
        $amountInWords = str_ireplace(
            ['Dollars Only', 'Dollar Only', 'Dollars', 'Dollar'],
            ['Rupees Only', 'Rupee Only', 'Rupees', 'Rupee'],
            $amountInWords,
        );
    @endphp

    <table class="info-box" style="border-top: 1px solid #999; margin-top: 8px;">

        <tr>

            <td width="60%">

                <strong>Amount in Words:</strong><br>

                {{ $amountInWords }}

            </td>

            <td class="sign-col">

                For Veltex Services Private Limited:

                <br>

                @if (file_exists(public_path('assets/images/signature.png')))
                    <img class="signature" src="{{ public_path('assets/images/signature.png') }}" alt="Signature">
                @else
                    <br><br><br>
                @endif

                <br>

                Authorized Signatory

            </td>

        </tr>

    </table>

    {{-- PAYMENT --}}

    @php
        $payment = $order->payment;

        // COD / COD advance keeps the existing single-line payment display.
        $isCodPayment =
            in_array(strtolower((string) ($payment->payment_gateway ?? '')), ['cod', 'cod_advance_paid'], true) ||
            (bool) ($order->is_cod_advance ?? false) ||
            $codCharge > 0;

        $paymentTransactionId = $payment->transaction_id ?? null;
        $paymentDateTime = $order->paid_at ?? ($payment->created_at ?? $order->created_at);

        $paymentMode = strtoupper((string) ($payment->payment_mode ?? 'PREPAID'));
    @endphp

    @if ($isCodPayment)
        {{-- COD: keep the existing format exactly as before --}}
        <div class="footer-note">
            Mode of Payment:
            {{ strtoupper($payment->payment_mode ?? 'COD') }}
        </div>
    @else
        {{-- PREPAID: show transaction ID, date/time and payment mode --}}
        <table class="payment-table">
            <tr>
                <td width="40%">
                    <strong>Payment Transaction ID:</strong>
                    {{ $paymentTransactionId ?: '-' }}
                </td>

                <td width="30%">
                    <strong>Date &amp; Time:</strong>
                    {{ $paymentDateTime ? \Illuminate\Support\Carbon::parse($paymentDateTime)->format('d/m/Y, h:i a') : '-' }}
                </td>

                <td width="30%">
                    <strong>Mode of Payment:</strong>
                    {{ $paymentMode }}
                </td>
            </tr>
        </table>
    @endif

</body>

</html>
