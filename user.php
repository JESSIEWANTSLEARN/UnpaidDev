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
       // 1. Update Profile / Account Details
    if ($action === 'update_profile') {
        $active_modal = 'details-modal';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

         if (!empty($name) && !empty($email)) {
            $stmt = $pdo->prepare("UPDATE wbo_users SET name = ?, email = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $_SESSION['user_id']]);
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;

            $message = "Account details updated successfully!";
            $message_type = "success";
            $active_modal = ''; // Close modal on success
              } else {
            $message = "Please fill in all required fields.";
            $message_type = "error";
        }
    }
         // 2. Update Account Settings / Preferences
    if ($action === 'update_settings') {
        $active_modal = 'settings-modal';
        $email_alerts = isset($_POST['email_alerts']) ? 1 : 0;
        $sms_alerts = isset($_POST['sms_alerts']) ? 1 : 0;

        // Save preferences to DB...
        $message = "Notification preferences saved!";
        $message_type = "success";
        $active_modal = ''; // Close modal on success
    }
         // 3. Update Password
    if ($action === 'update_password') {
        $active_modal = 'security-modal';
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';




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
