<!DOCTYPE html>

<html>

<head>
    <meta charset="UTF-8">
    <title>Order Shipped</title>
</head>

<body style="margin:0;background:#fff3e0;font-family:Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="padding:15px;">
        <tr>
            <td align="center">

                <table width="100%" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;">

                    <!-- HEADER -->
                    <tr>
                        <td
                            style="background:linear-gradient(135deg,#e65100,#fb8c00);padding:22px;text-align:center;color:#fff;">

                            <img src="{{ config('app.url') }}/assets/images/favicon.png" alt="Astrotring" width="70"
                                style="display:block;margin:0 auto 10px auto;">

                            <h2 style="margin:0;color:#fff;">
                                Astrotring
                            </h2>

                            <p style="margin:5px 0 0;font-size:13px;">
                                ✨ Spiritual & Gemstone Store ✨
                            </p>

                        </td>
                    </tr>

                    <!-- SHIPPED MESSAGE -->
                    <tr>
                        <td style="padding:25px;text-align:center;">

                            <h2 style="margin:0;color:#e65100;">
                                🚚 Order Shipped Successfully
                            </h2>

                            <p style="color:#555;font-size:14px;line-height:22px;">

                                Hi <b>{{ $order->name ?? ($order->user->name ?? 'Customer') }}</b>,

                                <br><br>

                                Your order has been shipped successfully and is on the way 🎉

                                <br>

                                You can track your shipment using the button below.

                            </p>

                            <p style="font-size:13px;color:#888;">
                                Order ID:
                                <strong>#{{ $order->order_number }}</strong>
                            </p>

                        </td>
                    </tr>

                    <!-- ORDER INFO -->
                    <tr>
                        <td style="padding:0 15px 15px;">

                            <table width="100%" style="background:#fff8f0;border-radius:10px;padding:12px;">

                                <tr>
                                    <td>
                                        <strong>Order Number</strong>
                                    </td>

                                    <td align="right">
                                        {{ $order->order_number }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <strong>Tracking Number</strong>
                                    </td>

                                    <td align="right">
                                        {{ $order->awb_code }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <strong>Order Date</strong>
                                    </td>

                                    <td align="right">
                                        {{ $order->created_at->format('d M Y h:i A') }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <strong>Total Amount</strong>
                                    </td>

                                    <td align="right" style="color:#e65100;font-weight:bold;">

                                        ₹{{ number_format($order->total_amount, 2) }}

                                    </td>
                                </tr>

                            </table>

                        </td>
                    </tr>

                    <!-- TRACK BUTTON -->
                    <tr>
                        <td style="padding:10px 20px 20px;text-align:center;">

                            <p style="margin:0 0 15px;font-size:14px;color:#555;line-height:22px;">
                                <strong>Tracking Number:</strong>
                                <span style="color:#e65100;font-weight:bold;">
                                    {{ $order->awb_code }}
                                </span>
                                <br>
                                Enter your tracking number on the DTDC tracking page to check the latest shipment
                                status.
                            </p>

                            <a href="https://www.dtdc.com/track-your-shipment" target="_blank"
                                style="
                                        background:#e65100;
                                        color:#fff;
                                        text-decoration:none;
                                        padding:14px 30px;
                                        border-radius:8px;
                                        display:inline-block;
                                        font-weight:bold;
                                        font-size:15px;
                                ">
                                📦 Track Your Order on DTDC
                            </a>

                        </td>
                    </tr>

                    <!-- PRODUCTS -->
                    <tr>
                        <td style="padding:10px 15px;">

                            @foreach ($order->items as $item)
                                <table width="100%"
                                    style="border:1px solid #f1f1f1;border-radius:10px;margin-bottom:10px;background:#fffaf5;">

                                    <tr>

                                        <td width="80" style="padding:10px;">

                                            @if ($item->product_image)
                                                <img src="{{ asset('storage/product/' . $item->product_image) }}"
                                                    width="65" style="border-radius:8px;">
                                            @endif

                                        </td>

                                        <td style="font-size:14px;">

                                            <b>{{ $item->product_name }}</b>

                                            <br>

                                            <span style="color:#777;">
                                                Qty: {{ $item->quantity }}
                                            </span>

                                        </td>

                                        <td align="right" style="color:#e65100;font-weight:bold;padding-right:10px;">

                                            ₹{{ number_format($item->total, 2) }}

                                        </td>

                                    </tr>

                                </table>
                            @endforeach

                        </td>
                    </tr>

                    <!-- TOTAL -->
                    <tr>
                        <td style="padding:15px;">

                            <table width="100%" style="background:#fff8f0;border-radius:10px;padding:10px;">

                                <tr>

                                    <td>
                                        Total Paid
                                    </td>

                                    <td align="right" style="color:#e65100;font-size:20px;">

                                        <b>
                                            ₹{{ number_format($order->total_amount, 2) }}
                                        </b>

                                    </td>

                                </tr>

                            </table>

                        </td>
                    </tr>

                    <!-- INVOICE -->
                    <tr>
                        <td style="padding:10px 20px;">

                            <div
                                style="
                            background:#fff8f0;
                            border:1px solid #ffe0b2;
                            border-radius:10px;
                            padding:12px;
                            color:#666;
                            font-size:13px;
                            text-align:center;
                        ">

                                📄 Invoice PDF is attached with this email.

                            </div>

                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background:#ffe0b2;text-align:center;padding:18px;font-size:12px;color:#555;">

                            🙏 Thank you for trusting Astrotring 💫

                            <br>

                            We wish you positivity, prosperity & success 🌟

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
