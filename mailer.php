<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_otp_email(
    string $toEmail,
    string $toName,
    string $otpCode
): bool {

    if (empty($toEmail)) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = SMTP_HOST;

        $mail->SMTPAuth = true;

        $mail->Username = SMTP_USERNAME;

        // Remove spaces from Google App Password
        $mail->Password = str_replace(
            ' ',
            '',
            SMTP_APP_PASSWORD
        );

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = SMTP_PORT;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            SMTP_USERNAME,
            SMTP_FROM_NAME
        );

        $mail->addAddress(
            $toEmail,
            $toName
        );

        $mail->isHTML(true);

        $mail->Subject =
            'WalangBrownout OTP Verification';

        $safeName = htmlspecialchars(
            $toName,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeOtp = htmlspecialchars(
            $otpCode,
            ENT_QUOTES,
            'UTF-8'
        );

        $mail->Body = "
            <div style='
                font-family: Arial, sans-serif;
                max-width: 500px;
                margin: auto;
                padding: 25px;
            '>

                <h2 style='color:#0E5BA8;'>
                    WalangBrownout
                </h2>

                <p>
                    Hello <strong>{$safeName}</strong>,
                </p>

                <p>
                    Your OTP verification code is:
                </p>

                <h1 style='
                    color:#0E5BA8;
                    letter-spacing:8px;
                '>
                    {$safeOtp}
                </h1>

                <p>
                    This code will expire in 5 minutes.
                </p>

                <p>
                    If you did not request this login,
                    you may ignore this email.
                </p>

            </div>
        ";

        $mail->AltBody =
            "Your WalangBrownout OTP code is: "
            . $otpCode
            . ". This code expires in 5 minutes.";

        $mail->send();

        return true;

    } catch (Exception $e) {

        echo "
            <div style='
                font-family:Arial;
                margin:40px;
                padding:20px;
                border:1px solid red;
                background:#fff5f5;
            '>

                <h2 style='color:red;'>
                    PHPMailer Error
                </h2>

                <p>"
                . htmlspecialchars(
                    $mail->ErrorInfo
                )
                . "</p>

            </div>
        ";

        exit();
    }
}