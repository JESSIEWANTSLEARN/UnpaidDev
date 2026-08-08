<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/audit.php';


// ==========================================
// DATABASE CHECK
// ==========================================

if (
    !isset($pdo) ||
    !($pdo instanceof PDO)
) {
    die('Database connection is unavailable.');
}


// ==========================================
// ALREADY LOGGED IN
// ==========================================

if (
    !empty($_SESSION['logged_in']) &&
    !empty($_SESSION['role'])
) {

    redirect_to_dashboard(
        $_SESSION['role']
    );
}


// ==========================================
// MAKE SURE USER CAME FROM SIGNUP
// ==========================================

if (
    !isset($_SESSION['signup_pending_user']) ||
    !isset($_SESSION['signup_otp_code'])
) {

    header('Location: signup.php');
    exit();
}


$error = '';
$success = '';



// ==========================================
// DEFAULT RESEND VALUES
// ==========================================

$_SESSION['signup_otp_resend_count'] =
    $_SESSION['signup_otp_resend_count'] ?? 0;

$_SESSION['signup_otp_last_sent'] =
    $_SESSION['signup_otp_last_sent'] ?? time();


