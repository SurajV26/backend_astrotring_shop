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
                            <h2 style="margin:0;">🎉 Affiliate Account Approved</h2>
                            <p style="margin:8px 0 0;font-size:13px;">
                                Welcome to Astrotring Affiliate Program
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
                                Congratulations! 🎉
                            </p>

                            <p style="color:#555;line-height:1.7;">
                                Your affiliate account has been successfully approved by the Astrotring team.
                            </p>

                            @if ($user->date_of_joining)
                                <p style="color:#555;line-height:1.7;">
                                    <strong>Date of Joining:</strong>
                                    {{ \Carbon\Carbon::parse($user->date_of_joining)->format('d M Y') }}
                                </p>
                            @endif

                            <p style="color:#555;line-height:1.7;">
                                You can now login and start promoting Astrotring products and services.
                            </p>

                            <!-- LOGIN BUTTON -->
                            <div style="text-align:center;margin:35px 0;">
                                <a href="{{ $loginUrl }}"
                                    style="
                                    background:#16a34a;
                                    color:#ffffff;
                                    text-decoration:none;
                                    padding:14px 30px;
                                    border-radius:8px;
                                    font-weight:bold;
                                    display:inline-block;
                                ">
                                    Login Now
                                </a>
                            </div>

                            <p style="color:#555;">
                                We look forward to a successful partnership with you.
                            </p>

                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background:#f3f4f6;padding:18px;text-align:center;font-size:12px;color:#666;">
                            Thank you for joining Astrotring Shop🚀
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
