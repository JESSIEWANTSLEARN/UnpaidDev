<?php

session_start();

// Remove all session variables
$_SESSION = [];

// Remove PHP session cookie
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Completely destroy session
session_destroy();

// Return to login
header('Location: login.php');
exit();
?>