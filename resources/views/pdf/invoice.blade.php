@php
    $priceBreakdown = is_array($order->price_breakdown)
        ? $order->price_breakdown
        : (json_decode($order->price_breakdown ?? '{}', true) ?:
        []);

    $itemTaxDetails = $priceBreakdown['item_tax_details'] ?? [];
    $items = $order->items ?? collect();

    // Prefer the stored order-level coupon amount, ignoring zero placeholders.
    // Some older orders store a zero in one key while the real value is in another.
    $couponDiscount = 0.0;
    foreach (
        [
            $priceBreakdown['coupon_discount_taxable_amount'] ?? null,
            $priceBreakdown['coupon_discount'] ?? null,
            $order->discount ?? null,
        ]
        as $couponCandidate
    ) {
        if (is_numeric($couponCandidate) && (float) $couponCandidate > 0) {
            $couponDiscount = round((float) $couponCandidate, 2);
            break;
        }
    }

    // Build each product's original tax split before determining/allocating the coupon.
$preCouponItems = [];
$originalTaxableSubtotal = 0.0;
$observedItemDiscountTotal = 0.0;
foreach ($items as $preItem) {
    $preInfo = $itemTaxDetails[$preItem->id] ?? ($itemTaxDetails[(string) $preItem->id] ?? []);
    $qty = max(1, (int) ($preItem->quantity ?? 1));
    $gross = round((float) ($preInfo['original_line_amount'] ?? (float) ($preItem->price ?? 0) * $qty), 2);
    $rate = (float) ($preInfo['gst_rate'] ?? ($preItem->gst_rate ?? 0));
    $originalGst = $rate > 0 ? round(($gross * $rate) / (100 + $rate), 2) : 0.0;
    $originalTaxable = round(max(0, $gross - $originalGst), 2);
    $savedTaxable = round(
        (float) ($preInfo['net_taxable_amount'] ??
            ($preInfo['taxable_amount'] ?? ($preItem->taxable_amount ?? $originalTaxable))),
        2,
    );
    $lineDiscount = round((float) ($preInfo['discount_amount'] ?? ($preItem->discount_amount ?? 0)), 2);

    // Legacy orders may have discounted taxable amounts but a zero/missing discount_amount.
    if ($lineDiscount <= 0 && $savedTaxable < $originalTaxable - 0.01) {
        $lineDiscount = round($originalTaxable - $savedTaxable, 2);
    }
    $lineDiscount = min($originalTaxable, max(0, $lineDiscount));

    $preCouponItems[$preItem->id] = [
        'qty' => $qty,
        'gross' => $gross,
        'gst_rate' => $rate,
        'original_gst' => $originalGst,
        'original_taxable' => $originalTaxable,
        'saved_taxable' => $savedTaxable,
        'saved_discount' => $lineDiscount,
        'saved_gst' => $preInfo['gst_amount'] ?? ($preItem->gst_amount ?? null),
        'tax_type' => $preInfo['tax_type'] ?? ($preItem->tax_type ?? null),
        'hsn_code' => $preItem->hsn_code ?? null,
    ];
    $originalTaxableSubtotal += $originalTaxable;
    $observedItemDiscountTotal += $lineDiscount;
}
$originalTaxableSubtotal = round($originalTaxableSubtotal, 2);

// If the order-level value is missing, recover it from line discounts or the applied coupon.
if ($couponDiscount <= 0 && $observedItemDiscountTotal > 0.01) {
    $couponDiscount = round($observedItemDiscountTotal, 2);
}
if ($couponDiscount <= 0 && $order->coupon && $originalTaxableSubtotal > 0) {
    $couponType = strtolower((string) ($order->coupon->discount_type ?? ''));
    $couponValue = max(0, (float) ($order->coupon->discount_value ?? 0));
    if (in_array($couponType, ['percentage', 'percent', 'percenatge'], true)) {
        $couponDiscount = round(($originalTaxableSubtotal * $couponValue) / 100, 2);
    } else {
        $couponDiscount = round($couponValue, 2);
    }
}
$couponDiscount = round(min(max(0, $couponDiscount), $originalTaxableSubtotal), 2);

// Keep a single order-level coupon, distributing it proportionally for line GST calculations.
$itemCouponDiscounts = [];
$knownDiscountTotal = 0.0;
$itemsNeedingAllocation = [];
foreach ($preCouponItems as $id => $line) {
    if ($line['saved_discount'] > 0.01) {
        $itemCouponDiscounts[$id] = $line['saved_discount'];
        $knownDiscountTotal += $line['saved_discount'];
    } else {
        $itemCouponDiscounts[$id] = 0.0;
        $itemsNeedingAllocation[] = $id;
    }
}

if ($couponDiscount > 0) {
    if ($knownDiscountTotal > $couponDiscount && $knownDiscountTotal > 0) {
        // Scale legacy line discounts if their saved sum differs from the order coupon amount.
        $scale = $couponDiscount / $knownDiscountTotal;
        $scaledTotal = 0.0;
        $knownIds = array_keys(array_filter($itemCouponDiscounts, fn($v) => $v > 0));
        foreach ($knownIds as $position => $id) {
            $amount =
                $position === count($knownIds) - 1
                    ? round($couponDiscount - $scaledTotal, 2)
                    : round($itemCouponDiscounts[$id] * $scale, 2);
            $itemCouponDiscounts[$id] = max(0, min($preCouponItems[$id]['original_taxable'], $amount));
            $scaledTotal += $itemCouponDiscounts[$id];
        }
        $knownDiscountTotal = round($scaledTotal, 2);
    }

    $remainingDiscount = round(max(0, $couponDiscount - $knownDiscountTotal), 2);
    if ($remainingDiscount > 0 && count($itemsNeedingAllocation) > 0) {
        $weightTotal = 0.0;
        foreach ($itemsNeedingAllocation as $id) {
            $weightTotal += $preCouponItems[$id]['original_taxable'];
        }
        $allocated = 0.0;
        foreach ($itemsNeedingAllocation as $position => $id) {
            $weight = $preCouponItems[$id]['original_taxable'];
            $share =
                $position === count($itemsNeedingAllocation) - 1 || $weightTotal <= 0
                    ? round($remainingDiscount - $allocated, 2)
                    : round(($remainingDiscount * $weight) / $weightTotal, 2);
            $share = max(0, min($weight, $share));
            $itemCouponDiscounts[$id] = $share;
            $allocated += $share;
        }
    }
}

$invoiceItemRows = [];
$itemsTaxableTotal = 0.0;
$itemsGstTotal = 0.0;
$itemsNetTotal = 0.0;
$itemsOriginalTaxableTotal = 0.0;
$itemsOriginalGstTotal = 0.0;
foreach ($preCouponItems as $id => $line) {
    $originalTaxable = $line['original_taxable'];
    $lineDiscount = $couponDiscount > 0 ? round((float) ($itemCouponDiscounts[$id] ?? 0), 2) : 0.0;
    $savedTaxableIsDiscounted = $line['saved_taxable'] < $originalTaxable - 0.01;
    $netTaxable =
        $couponDiscount > 0
            ? ($savedTaxableIsDiscounted
                ? $line['saved_taxable']
                : round(max(0, $originalTaxable - $lineDiscount), 2))
            : $originalTaxable;
    $lineDiscount = round(max(0, $originalTaxable - $netTaxable), 2);

    if ($couponDiscount > 0) {
        $expectedNetGst =
            $line['gst_rate'] > 0
                ? round(($netTaxable * $line['gst_rate']) / 100, 2)
                : round(max(0, $line['gross'] - $netTaxable), 2);
        $savedGst = is_numeric($line['saved_gst']) ? round((float) $line['saved_gst'], 2) : null;
        // Don't reuse the old pre-discount GST if it doesn't match the recalculated amount.
        $netGst = $savedGst !== null && abs($savedGst - $expectedNetGst) <= 0.01 ? $savedGst : $expectedNetGst;
        $netTotal = round($netTaxable + $netGst, 2);
    } else {
        $netGst = $line['original_gst'];
        $netTotal = $line['gross'];
    }

    $invoiceItemRows[$id] = [
        'unit_price_ex_tax' => round($line['original_taxable'] / max(1, $line['qty']), 2),
        'quantity' => $line['qty'],
        'discount' => $lineDiscount,
        'taxable' => $netTaxable,
        'gst_rate' => $line['gst_rate'],
        'tax_type' => $line['tax_type'],
        'gst_amount' => $netGst,
        'line_total' => $netTotal,
    ];
    $itemsOriginalTaxableTotal += $originalTaxable;
    $itemsOriginalGstTotal += $line['original_gst'];
    $itemsTaxableTotal += $netTaxable;
    $itemsGstTotal += $netGst;
    $itemsNetTotal += $netTotal;
}

$itemsOriginalTaxableTotal = round($itemsOriginalTaxableTotal, 2);
$itemsOriginalGstTotal = round($itemsOriginalGstTotal, 2);
$itemsTaxableTotal = round($itemsTaxableTotal, 2);
$itemsGstTotal = round($itemsGstTotal, 2);
$itemsNetTotal = round($itemsNetTotal, 2);

$deliveryCharge = round((float) ($order->delivery_charge ?? ($priceBreakdown['delivery_charge'] ?? 0)), 2);
$shippingGstRate = round((float) ($priceBreakdown['shipping_gst_rate'] ?? 18), 2);
$shippingTaxableAmount = round((float) ($priceBreakdown['shipping_taxable_amount'] ?? 0), 2);
$shippingGstAmount = round((float) ($priceBreakdown['shipping_gst_amount'] ?? 0), 2);
if ($deliveryCharge > 0 && $shippingGstRate > 0) {
    // Use the GST-inclusive delivery charge as the source of truth for its split.
    $shippingTaxableAmount = round($deliveryCharge / (1 + $shippingGstRate / 100), 2);
    $shippingGstAmount = round($deliveryCharge - $shippingTaxableAmount, 2);
} else {
    $shippingTaxableAmount = $deliveryCharge;
    $shippingGstAmount = 0.0;
}

$codCharge = round((float) ($priceBreakdown['cod_charge'] ?? 0), 2);
$codGstRate = round((float) ($priceBreakdown['cod_gst_rate'] ?? 18), 2);
if ($codCharge > 0 && $codGstRate > 0) {
    $codTaxableAmount = round($codCharge / (1 + $codGstRate / 100), 2);
    $codGstAmount = round($codCharge - $codTaxableAmount, 2);
} else {
    $codTaxableAmount = $codCharge;
    $codGstAmount = 0.0;
}

$orderTaxableAmount = round((float) ($order->taxable_amount ?? ($priceBreakdown['taxable_amount'] ?? 0)), 2);
$orderGstRate = round((float) ($order->gst_rate ?? ($priceBreakdown['gst_rate'] ?? 0)), 2);
$orderCgstAmount = round((float) ($order->cgst_amount ?? ($priceBreakdown['cgst_amount'] ?? 0)), 2);
$orderSgstAmount = round((float) ($order->sgst_amount ?? ($priceBreakdown['sgst_amount'] ?? 0)), 2);
$orderIgstAmount = round((float) ($order->igst_amount ?? ($priceBreakdown['igst_amount'] ?? 0)), 2);
$orderTaxType = $order->tax_type ?? ($priceBreakdown['tax_type'] ?? null);
$totalGstAmount = round($orderCgstAmount + $orderSgstAmount + $orderIgstAmount, 2);

$couponCode = $order->coupon->code ?? null;
$couponRateLabel = '';
if ($order->coupon) {
    $couponType = strtolower((string) ($order->coupon->discount_type ?? ''));
    if (in_array($couponType, ['percentage', 'percent', 'percenatge'], true)) {
        $couponRateLabel =
            ' (' .
            rtrim(rtrim(number_format((float) ($order->coupon->discount_value ?? 0), 2, '.', ''), '0'), '.') .
            '%)';
        }
    }
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

            font-size: 8.8px;

            table-layout: fixed;

        }



        table.items th,

        table.items td {

            border: 1px solid #999;

            padding: 4px 2px;

            text-align: center;

            overflow-wrap: break-word;

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

        .items tr.final-total-row td {
            font-weight: bold;
            background: #f2f2f2;
            padding-top: 7px;
            padding-bottom: 7px;
            border-top: 1px solid #555;
        }

        .items tr.final-total-row td.total-label {
            text-align: left;
            border-right: 1px solid #999;
            padding-left: 8px;
        }

        .items tr.final-total-row td.total-amount {
            text-align: center !important;
            white-space: nowrap;
            padding-left: 4px;
            padding-right: 4px;
        }



        /* Order-level coupon summary: show the coupon only once, not once per item. */

        table.order-summary {

            margin-top: 8px;

            width: 100%;

            font-size: 10px;

            border-collapse: collapse;

        }



        table.order-summary td {

            border: 1px solid #999;

            padding: 5px 7px;

            vertical-align: top;

        }



        table.order-summary td.amount {

            text-align: right;

            white-space: nowrap;

            width: 25%;

        }



        table.order-summary tr.final-product-row td,

        table.order-summary tr.grand-total-row td {

            font-weight: bold;

            background: #f2f2f2;

        }



        .coupon-explanation {

            font-size: 9px;

            color: #555;

            margin-top: 5px;

            line-height: 1.4;

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



    {{-- PRODUCT ITEMS: concise invoice layout. Coupon discount is allocated per line. --}}

    <table class="items">

        <thead>

            <tr>

                <th style="width:5%;">S.No</th>

                <th style="width:22%; text-align:left;">Product Description</th>

                <th style="width:9%;">Unit Price</th>

                <th style="width:6%;">Qty</th>

                <th style="width:11%;">Discount{{ $couponRateLabel }}</th>

                <th style="width:13%;">Taxable Amount<br>(after discount)</th>

                <th style="width:6%;">GST Rate</th>

                <th style="width:9%;">GST Type</th>

                <th style="width:9%;">GST Amount</th>

                <th style="width:10%;">Amount<br>(incl. tax)</th>

            </tr>

        </thead>

        <tbody>

            @foreach ($items as $index => $item)
                @php $invoiceItem = $invoiceItemRows[$item->id] ?? []; @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>

                    <td class="desc">

                        {{ $item->product_name }}

                        <small>HSN: {{ $item->hsn_code ?: '-' }}</small>

                    </td>

                    <td>₹{{ number_format((float) ($invoiceItem['unit_price_ex_tax'] ?? 0), 2) }}</td>

                    <td>{{ (int) ($invoiceItem['quantity'] ?? ($item->quantity ?? 1)) }}</td>

                    <td>

                        @if ($couponDiscount > 0 && (float) ($invoiceItem['discount'] ?? 0) > 0)
                            ₹{{ number_format((float) $invoiceItem['discount'], 2) }}
                        @else
                            —
                        @endif

                    </td>

                    <td>₹{{ number_format((float) ($invoiceItem['taxable'] ?? 0), 2) }}</td>

                    <td>{{ number_format((float) ($invoiceItem['gst_rate'] ?? 0), 2) }}%</td>

                    <td>

                        @if (($invoiceItem['tax_type'] ?? $item->tax_type) === 'cgst_sgst')
                            CGST<br> + <br>SGST
                        @elseif (($invoiceItem['tax_type'] ?? $item->tax_type) === 'igst')
                            IGST
                        @else
                            —
                        @endif

                    </td>

                    <td>₹{{ number_format((float) ($invoiceItem['gst_amount'] ?? 0), 2) }}</td>

                    <td><strong>₹{{ number_format(round((float) ($invoiceItem['taxable'] ?? 0) + (float) ($invoiceItem['gst_amount'] ?? 0), 2), 2) }}</strong>
                    </td>

                </tr>
            @endforeach



            {{-- Delivery charge is shown as a separate GST-inclusive line. --}}
            @if ($deliveryCharge > 0)
                <tr>
                    <td>{{ $items->count() + 1 }}</td>
                    <td class="desc">
                        <strong>Delivery Charge</strong>
                        <small>Delivery fee (GST included)</small>
                    </td>
                    <td>₹{{ number_format($shippingTaxableAmount, 2) }}</td>
                    <td>—</td>
                    <td>—</td>
                    <td>₹{{ number_format($shippingTaxableAmount, 2) }}</td>
                    <td>{{ number_format($shippingGstRate, 2) }}%</td>
                    <td>
                        @if ($orderTaxType === 'cgst_sgst')
                            CGST<br> + <br>SGST
                        @elseif ($orderTaxType === 'igst')
                            IGST
                        @else
                            —
                        @endif
                    </td>
                    <td>₹{{ number_format($shippingGstAmount, 2) }}</td>
                    <td><strong>₹{{ number_format($deliveryCharge, 2) }}</strong></td>
                </tr>
            @endif

            {{-- COD charge is shown as its own taxable line. The charge is GST-inclusive. --}}
            @if ($codCharge > 0)
                <tr>
                    <td>{{ $items->count() + ($deliveryCharge > 0 ? 2 : 1) }}</td>
                    <td class="desc">
                        <strong>COD Charge</strong>
                        <small>Cash on Delivery fee (GST included)</small>
                    </td>
                    <td>₹{{ number_format($codTaxableAmount, 2) }}</td>
                    <td>—</td>
                    <td>—</td>
                    <td>₹{{ number_format($codTaxableAmount, 2) }}</td>
                    <td>{{ number_format($codGstRate, 2) }}%</td>
                    <td>
                        @if ($orderTaxType === 'cgst_sgst')
                            CGST<br> + <br>SGST
                        @elseif ($orderTaxType === 'igst')
                            IGST
                        @else
                            —
                        @endif
                    </td>
                    <td>₹{{ number_format($codGstAmount, 2) }}</td>
                    <td><strong>₹{{ number_format($codCharge, 2) }}</strong></td>
                </tr>
            @endif

            {{-- Final payable total appears in the same table, with a vertical divider before the amount. --}}
            <tr class="final-total-row">
                <td colspan="9" class="total-label">FINAL ORDER TOTAL (amount payable)</td>
                <td class="total-amount">
                    <strong>₹{{ number_format(round($itemsNetTotal + $deliveryCharge + $codCharge, 2), 2) }}</strong>
                </td>
            </tr>

        </tbody>

    </table>



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
