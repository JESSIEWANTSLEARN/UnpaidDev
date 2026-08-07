<?php
// config.php
$host = 'localhost';
$dbname = 'WalangBrownout';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = null;

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    $pdo = null;
}

// --- Gmail SMTP settings for sending OTP emails ---
// SMTP_USERNAME must be a Gmail address with 2-Step Verification enabled.
// SMTP_APP_PASSWORD must be a 16-character Google App Password (NOT your normal Gmail password).
// Generate one at: https://myaccount.google.com/apppasswords
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'yourwebsite@gmail.com');
define('SMTP_APP_PASSWORD', 'xxxx xxxx xxxx xxxx');
define('SMTP_FROM_NAME', 'WalangBrownout');
