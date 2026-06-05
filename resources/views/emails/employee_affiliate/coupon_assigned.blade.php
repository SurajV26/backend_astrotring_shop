<!DOCTYPE html>

<html>

<head>
    <meta charset="UTF-8">
</head>

<body style="margin:0;background:#f5f7fb;font-family:Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="padding:20px;">
        <tr>
            <td align="center">

                <table width="100%"
                    style="max-width:650px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">

                    <!-- HEADER -->
                    <tr>
                        <td
                            style="background:linear-gradient(169deg,#e58146,#ffc107e0);padding:24px;text-align:center;color:#fff;">
                            <h2 style="margin:0;">🎁 New Affiliate Coupon Assigned</h2>
                            <p style="margin:8px 0 0;font-size:13px;">
                                Astrotring Affiliate Program
                            </p>
                        </td>
                    </tr>

                    <!-- CONTENT -->
                    <tr>
                        <td style="padding:30px;">

                            <h3 style="margin-top:0;color:#333;">
                                Hello {{ $user->name }},
                            </h3>

                            <p style="color:#555;line-height:1.7;">
                                A coupon has been assigned to your affiliate account.
                            </p>

                            <!-- COUPON BOX -->
                            <div
                                style="background:#fff7ed;border:2px dashed #f59e0b;border-radius:12px;padding:25px;text-align:center;margin:25px 0;">

                                <p style="margin:0;color:#777;font-size:13px;">
                                    YOUR COUPON CODE
                                </p>

                                <h1
                                    style="
                                        margin:10px 0 0;
                                        color:#e65100;
                                        letter-spacing:3px;
                                        font-size:34px;
                                    ">
                                    {{ $coupon->code }}
                                </h1>

                            </div>

                            <!-- DETAILS -->
                            <table width="100%"
                                style="background:#fafafa;border:1px solid #ececec;border-radius:10px;padding:15px;">

                                <tr>
                                    <td style="padding:8px 0;"><strong>Coupon Code</strong></td>
                                    <td align="right">{{ $coupon->code }}</td>
                                </tr>

                                <tr>
                                    <td style="padding:8px 0;"><strong>Discount Type</strong></td>
                                    <td align="right">
                                        {{ ucfirst($coupon->discount_type) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:8px 0;"><strong>Discount Value</strong></td>
                                    <td align="right">
                                        @if ($coupon->discount_type == 'percentage')
                                            {{ $coupon->discount_value }}%
                                        @else
                                            ₹{{ number_format($coupon->discount_value, 2) }}
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:8px 0;"><strong>Minimum Order Amount</strong></td>
                                    <td align="right">
                                        ₹{{ number_format($coupon->min_amount ?? 0, 2) }}
                                    </td>
                                </tr>

                                @if ($coupon->max_discount)
                                    <tr>
                                        <td style="padding:8px 0;"><strong>Maximum Discount</strong></td>
                                        <td align="right">
                                            ₹{{ number_format($coupon->max_discount, 2) }}
                                        </td>
                                    </tr>
                                @endif

                                <tr>
                                    <td style="padding:8px 0;"><strong>Expiry Date</strong></td>
                                    <td align="right">
                                        {{ \Carbon\Carbon::parse($coupon->expiry_date)->format('d M Y') }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:8px 0;"><strong>Status</strong></td>
                                    <td align="right">
                                        {{ $coupon->status ? 'Active' : 'Inactive' }}
                                    </td>
                                </tr>

                            </table>

                            <p style="margin-top:25px;color:#555;line-height:1.7;">
                                Please use this coupon while promoting Astrotring products and services.
                            </p>

                            <p style="color:#555;">
                                Wishing you great success with your affiliate campaigns 🚀
                            </p>

                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background:#f3f4f6;padding:18px;text-align:center;font-size:12px;color:#666;">
                            Thank you for being a valued Astrotring Affiliate ❤️
                            <br>
                            Astrotring Affiliate Team
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
