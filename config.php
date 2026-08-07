<?php

// =====================================================
// DATABASE
// =====================================================

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

    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        $options
    );

} catch (PDOException $e) {

    error_log(
        'Database connection failed: ' .
        $e->getMessage()
    );

    $pdo = null;
}


// =====================================================
// GMAIL SMTP
// =====================================================

define(
    'SMTP_HOST',
    'smtp.gmail.com'
);

define(
    'SMTP_PORT',
    587
);


// Gmail account that sends the OTP
define(
    'SMTP_USERNAME',
    'ENTER YOUR GMAIL ACCOUNT HERE'
);


// =====================================================
// GOOGLE APP PASSWORD
// =====================================================
//
// IMPORTANT:
//
// HINDI normal Gmail password ang ilalagay dito.
//
// Kailangan Google APP PASSWORD.
//
// Example only:
//
// abcd efgh ijkl mnop
//
// =====================================================

define(
    'SMTP_APP_PASSWORD',
    'ENTER YOUT PASSWORD HERE
'
);


define(
    'SMTP_FROM_NAME',
    'WalangBrownout'
);

?>