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

    // Make sure recipient email exists
    if (empty($toEmail)) {

        error_log(
            'OTP email failed: recipient email is empty.'
        );

        return false;
    }


    $mail = new PHPMailer(true);


    try {

        // =============================================
        // SMTP CONFIGURATION
        // =============================================

        $mail->isSMTP();

        $mail->Host = SMTP_HOST;

        $mail->SMTPAuth = true;

        $mail->Username = SMTP_USERNAME;


        // Remove spaces, tabs, and accidental new lines
        // from Google App Password
        $mail->Password = preg_replace(
            '/\s+/',
            '',
            SMTP_APP_PASSWORD
        );


        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = SMTP_PORT;

        $mail->CharSet = 'UTF-8';


        // =============================================
        // SENDER
        // =============================================

        $mail->setFrom(
            SMTP_USERNAME,
            SMTP_FROM_NAME
        );


        // =============================================
        // RECIPIENT
        // =============================================
        //
        // $toEmail comes from the user's database.
        // This means the OTP goes to the user who
        // is actually logging in.
        //
        // =============================================

        $mail->addAddress(
            $toEmail,
            $toName
        );


        // =============================================
        // EMAIL CONTENT
        // =============================================

        $mail->isHTML(true);

        $mail->Subject =
            'WalangBrownout OTP Verification';


        $safeName = htmlspecialchars(
            $toName ?: 'User',
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
                    This code will expire in
                    <strong>5 minutes</strong>.
                </p>


                <p>
                    If you did not request this login,
                    you may ignore this email.
                </p>

            </div>

        ";


        // Plain text version
        $mail->AltBody =
            "Hello {$toName},\n\n" .

            "Your WalangBrownout OTP code is: " .
            $otpCode .

            "\n\nThis code expires in 5 minutes." .

            "\n\nIf you did not request this login, " .
            "you may ignore this email.";


        // =============================================
        // SEND EMAIL
        // =============================================

        $mail->send();

        return true;


    } catch (Exception $e) {

        // Store technical error in PHP log
        error_log(
            'OTP email failed: ' .
            $mail->ErrorInfo
        );

        // login.php receives false
        return false;
    }
}