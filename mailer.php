<?php
// mailer.php
// Uses PHPMailer, installed via Composer.
// Run this once in your project folder before this file will work:
//   composer require phpmailer/phpmailer
 
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
 
**
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
 