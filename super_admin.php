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
