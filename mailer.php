<?php
// mailer.php
// Uses PHPMailer, installed via Composer.
// Run this once in your project folder before this file will work:
//   composer require phpmailer/phpmailer
 
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
 
/**
 * Sends a one-time password code to the given email address via Gmail SMTP.
 *
 * @param string $toEmail Recipient's own email address (not a hardcoded one).
 * @param string $toName  Recipient's display name.
 * @param string $otpCode The 6-digit OTP code to send.
 * @return bool true on success, false on failure (check error_log for details).
 */
function send_otp_email(string $toEmail, string $toName, string $otpCode): bool
{
    if (empty($toEmail)) {
        error_log('OTP email failed: no recipient email address provided.');
        return false;
    }
 
    $mail = new PHPMailer(true);
 
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
 
        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
 
        $mail->isHTML(true);
        $mail->Subject = 'Your WalangBrownout verification code';
        $mail->Body = "Hi " . htmlspecialchars($toName ?: 'there') . ",<br><br>"
            . "Your OTP code is: <strong style=\"font-size:20px;\">" . htmlspecialchars($otpCode) . "</strong><br><br>"
            . "Enter this code to finish logging in. If you didn't request this, you can ignore this email.";
        $mail->AltBody = "Your OTP code is: $otpCode";
 
        $mail->send();
        return true;
 } catch (Exception $e) {
        error_log('OTP email failed: ' . $mail->ErrorInfo);
        return false;
    }
}