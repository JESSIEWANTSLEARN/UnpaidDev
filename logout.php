<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/audit.php';


if (
    !isset($pdo) ||
    !($pdo instanceof PDO)
) {
    die('Database connection is unavailable.');
}


// ==========================================
// RECORD LOGOUT
// ==========================================

if (
    !empty($_SESSION['logged_in']) &&
    !empty($_SESSION['user_id'])
) {

    log_activity(
        $pdo,
        (int) $_SESSION['user_id'],
        'LOGOUT',
        'User logged out'
    );
}


// ==========================================
// CLEAR SESSION
// ==========================================

$_SESSION = [];


// ==========================================
// DELETE SESSION COOKIE
// ==========================================

if (ini_get('session.use_cookies')) {

    $params =
        session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}


// ==========================================
// DESTROY SESSION
// ==========================================

session_destroy();


// ==========================================
// RETURN TO LOGIN
// ==========================================

header('Location: login.php');
exit();