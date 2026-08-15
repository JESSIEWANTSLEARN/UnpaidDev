<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WalangBrownout OTP</title>
</head>

<body style="
    margin: 0;
    padding: 30px;
    background: #f3f4f6;
    font-family: Arial, sans-serif;
">

    <div style="
        max-width: 500px;
        margin: auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
    ">

        <h2 style="color: #1D4ED8;">
            WalangBrownout
        </h2>

        <p>
            Hello <strong>{{ $userName }}</strong>,
        </p>

        <p>Your OTP verification code is:</p>

        <h1 style="
            color: #1D4ED8;
            letter-spacing: 8px;
        ">
            {{ $otpCode }}
        </h1>

        <p>
            This code will expire in
            <strong>5 minutes</strong>.
        </p>

        <p style="color: #6B7280;">
            If you did not request this verification,
            you may ignore this email.
        </p>

    </div>

</body>
</html>