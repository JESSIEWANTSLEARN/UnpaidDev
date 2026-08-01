<?php
require_once 'session.php';
check_access('staff_admin');

$message = '';
$message_type = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // 1. Update Staff Admin Profile Details
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department'] ?? '');
        
   if (!empty($name) && !empty($email)) {
            if (isset($pdo)) {
                $stmt = $pdo->prepare("UPDATE wbo_users SET name = ?, email = ? WHERE user_id = ?");
                $stmt->execute([$name, $email, $_SESSION['user_id']]);
            }
                  $_SESSION['name'] = $name; // Update active session
            $message = "Profile details updated successfully!";
            $message_type = "success";
        } else {
            $message = "Please fill in all required fields.";
            $message_type = "error";
        }
    }
/ 2. Update HR Admin Preferences
    if ($action === 'update_settings') {
        $roster_alerts = isset($_POST['roster_alerts']) ? 1 : 0;
        $daily_summary = isset($_POST['daily_summary']) ? 1 : 0;

        // Save preferences to DB...
        $message = "Administrative preferences saved successfully!";
        $message_type = "success";
    }
         if ($action === 'update_password') {
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
