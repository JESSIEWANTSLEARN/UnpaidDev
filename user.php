<?php
require_once 'session.php';
check_access('user');

// Generate CSRF Token if not present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$message = '';
$message_type = '';
$active_modal = ''; // Tracks which modal to reopen on error

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF Protection Check
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die("Invalid CSRF token submission.");
    }

    $action = $_POST['action'] ?? '';



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WalangBrownout</title>
    <link rel="icon" type="image/png" href="image/Logo.png"> <!-- Placeholder for client icon -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #F2F2F2;
            color: #0E5BA8;
        }

        header {
            margin-bottom: 20px;
            padding: 20px;
            text-align: left;
        }

        footer {
            padding: 10px;
            text-align: center;
        }
        
    </style>

</head>
<body>
    <header>
        <nav>
            
        </nav>
    </header>

    <main>
        
    </main>

    <footer>
        <p><strong>&copy; 2026 WalangBrownout. All rights reserved.</strong></p>
    </footer>
</body>
</html>

<?php

?>