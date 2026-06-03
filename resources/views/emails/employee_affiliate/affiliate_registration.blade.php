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
                            style="background: linear-gradient(169deg, #e58146, #ffc107e0);padding: 24px;text-align: center;color: #fff;">
                            <h2 style="margin:0;">🤝 New Affiliate Registration</h2>
                            <p style="margin:8px 0 0;font-size:13px;">
                                Astrotring Affiliate Program
                            </p>
                        </td>
                    </tr>

                    <!-- CONTENT -->
                    <tr>
                        <td style="padding:25px;">

                            <h3 style="margin-top:0;color:#333;">
                                A new affiliate has submitted a registration request.
                            </h3>

                            <table width="100%"
                                style="border:1px solid #e5e7eb;border-radius:10px;background:#fafafa;padding:15px;">

                                <tr>
                                    <td style="padding:8px 0;"><strong>Name:</strong></td>
                                    <td>{{ $user->name }}</td>
                                </tr>

                                <tr>
                                    <td style="padding:8px 0;"><strong>Email:</strong></td>
                                    <td>{{ $user->email }}</td>
                                </tr>

                                <tr>
                                    <td style="padding:8px 0;"><strong>Mobile:</strong></td>
                                    <td>{{ $user->country_code }} {{ $user->mobile }}</td>
                                </tr>

                                <tr>
                                    <td style="padding:8px 0;"><strong>Username:</strong></td>
                                    <td>{{ $user->username }}</td>
                                </tr>

                                @if ($user->company_name)
                                    <tr>
                                        <td style="padding:8px 0;"><strong>Company:</strong></td>
                                        <td>{{ $user->company_name }}</td>
                                    </tr>
                                @endif

                                <tr>
                                    <td style="padding:8px 0;"><strong>Affiliate Type:</strong></td>
                                    <td>{{ ucfirst($user->affiliate_type) }}</td>
                                </tr>

                                @if ($user->expected_leads)
                                    <tr>
                                        <td style="padding:8px 0;"><strong>Expected Leads:</strong></td>
                                        <td>{{ str_replace('_', ' ', $user->expected_leads) }}</td>
                                    </tr>
                                @endif

                                @if ($user->promotion_plan)
                                    <tr>
                                        <td style="padding:8px 0;"><strong>Promotion Plan:</strong></td>
                                        <td>{{ $user->promotion_plan }}</td>
                                    </tr>
                                @endif

                            </table>

                            <p style="margin-top:20px;color:#555;">
                                Please review this affiliate request from the admin panel and approve/reject
                                accordingly.
                            </p>

                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background:#f3f4f6;padding:18px;text-align:center;font-size:12px;color:#666;">
                            Astrotring Shop Admin Notification System
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
