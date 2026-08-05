<?php
require_once 'session.php';
check_access('super_admin');
// Database connection assumed via db.php or initialized in session.php
// require_once 'db.php'; 

$message = '';
$message_type = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
      // 1. Update Profile
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
                if (!empty($name) && !empty($email)) {
            // Update database (Adjust table/column names to match your schema)
            if (isset($pdo)) {
                $stmt = $pdo->prepare("UPDATE wbo_users SET name = ?, email = ? WHERE user_id = ?");
                $stmt->execute([$name, $email, $_SESSION['user_id']]);
            }
                  $_SESSION['name'] = $name; // Update active session
            $_SESSION['email'] = $email;
            $message = "Profile updated successfully!";
            $message_type = "success";
                      } else {
            $message = "Please fill in all required fields.";
            $message_type = "error";
        }
    }
        // 2. Update System Settings
    if ($action === 'update_settings') {
        $site_title = trim($_POST['site_title'] ?? '');
        $maintenance = isset($_POST['maintenance_mode']) ? 1 : 0;
                // Save settings to DB or config file here...
        $message = "System settings saved successfully!";
        $message_type = "success";
    }
<<<<<<< HEAD

        // 3. Update Password
=======
    // 3. Update Password
>>>>>>> 3f1a6e3 (update files)
    if ($action === 'update_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $message = "All password fields are required.";
            $message_type = "error";
        } elseif ($new_pass !== $confirm_pass) {
            $message = "New passwords do not match.";
            $message_type = "error";
        } elseif (strlen($new_pass) < 8) {
            $message = "Password must be at least 8 characters long.";
            $message_type = "error";
        } else {
            if (isset($pdo) && isset($_SESSION['user_id'])) {
                // Verify current password and update in DB
                $stmt = $pdo->prepare("SELECT password_hash FROM wbo_users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();

                if ($user && password_verify($current_pass, $user['password_hash'])) {
                    $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE wbo_users SET password_hash = ? WHERE user_id = ?");
                    $update->execute([$hashed_pass, $_SESSION['user_id']]);
                    $message = "Password updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Incorrect current password.";
                    $message_type = "error";
                }
            } else {
                // Fallback for demo mode without direct PDO binding
                $message = "Password updated successfully (Demo Mode)!";
                $message_type = "success";
            }
        }
    }
}
<<<<<<< HEAD
?>
=======
>>>>>>> 3f1a6e3 (update files)
                
    

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
