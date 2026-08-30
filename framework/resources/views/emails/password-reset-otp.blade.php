<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6fb;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px;background:#071120;color:#ffffff;">
                            <div style="font-size:12px;font-weight:700;letter-spacing:.08em;color:#93c5fd;">WALANG BROWNOUT</div>
                            <div style="margin-top:7px;font-size:22px;font-weight:800;">Password Reset</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 28px;">
                            <p style="margin:0 0 12px;font-size:15px;">Hello {{ $userName }},</p>
                            <p style="margin:0 0 22px;font-size:14px;line-height:1.6;color:#475569;">
                                Use this one-time code to verify your password reset request.
                            </p>

                            <div style="padding:18px;text-align:center;border:1px solid #dbeafe;border-radius:14px;background:#eff6ff;">
                                <div style="font-size:11px;font-weight:700;letter-spacing:.08em;color:#2563eb;">RESET CODE</div>
                                <div style="margin-top:7px;font-size:34px;font-weight:900;letter-spacing:.18em;color:#0f172a;">
                                    {{ $otpCode }}
                                </div>
                            </div>

                            <p style="margin:22px 0 0;font-size:12px;line-height:1.6;color:#64748b;">
                                This code expires in {{ $expiryMinutes }} minute(s). If you did not request a password reset, you can ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
