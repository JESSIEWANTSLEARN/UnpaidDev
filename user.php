<?php
require_once 'session.php';
check_access('user');

if (!isset($pdo) || !$pdo instanceof PDO) {
    die("Database connection not initialized.");
}

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
            $stmt = $pdo->prepare("SELECT password_hash FROM wbo_users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_pass, $user['password_hash'])) {
                $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE wbo_users SET password_hash = ? WHERE user_id = ?");
                $update->execute([$hashed_pass, $_SESSION['user_id']]);

                $message = "Password updated successfully!";
                $message_type = "success";
                $active_modal = ''; // Close modal on success
            } else {
                $message = "Incorrect current password.";
                $message_type = "error";
            }
        }
    }

    // 4. Submit Support Ticket
    if ($action === 'submit_support') {
        $active_modal = 'support-modal';
        $subject = trim($_POST['subject'] ?? '');
        $message_text = trim($_POST['message'] ?? '');

        if (!empty($subject) && !empty($message_text)) {
            // Save ticket to DB...
            $message = "Your support ticket has been submitted. Our team will get back to you shortly!";
            $message_type = "success";
            $active_modal = ''; // Close modal on success
        } else {
            $message = "Please fill in both the subject and message fields.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>WalangBrownout</title>

    <link rel="icon" type="image/png" href="image/Logo.png">

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


        /* IMAGE CSS */

        .my-image {
            width: 300px;
            height: auto;
            border-radius: 10px;

            display: block;
            margin-left: auto;
            margin-right: auto;
        }

             .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 50px;
        }
        .image-box {
            display: inline-block;
            text-align: center;
        }
        img {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            transition: all 0.3s ease-in-out;
            cursor: pointer;
        }
        
        /* Hover Zoom Effect */
        .zoom img:hover {
            transform: scale(1.2);
        }
        
        /* Fade In on Hover */
        .fade img {
            opacity: 0.7;
        }
        .fade img:hover {
            opacity: 1;
        }
        
        /* Rotate Effect */
        .rotate img:hover {
            transform: rotate(15deg);
        }
        
        /* Moving (Slide-Up) on Hover */
        .slide-up img:hover {
            transform: translateY(-10px);
        }
        
        /* Shake Effect */
        .shake img:hover {
            animation: shake 0.5s infinite;
        }
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
        }
        erwr
        /* Flip Effect */
        .flip img, .test1 {
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }
        .flip img:hover {
            transform: rotateY(180deg);
        }
        
        /* Glowing Border Effect */
        .glow img {
            border: 3px solid transparent;
            transition: border-color 0.5s ease, box-shadow 0.5s ease;
        }
        .glow img:hover {
            border-color: blue;
            box-shadow: 0 0 15px blue;
        }



    </style>

</head>


<body>

    <header>

        <nav>

        </nav>

    </header>


    <!-- IMAGE -->

    <img
        src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQLX5EQYeKkqr9Nmg4goaFeRKP1Ps0bV5SkICFTDA-pog&s=10"
        alt="Image"
        class="my-image"
    >

   <img accesskey="
" src="https://media.tenor.com/el4CnBiSEyUAAAAM/shore-keeper-shorekeeper-eating.gif" alt="Image" class="my-image">


 
 
   <img accesskey="
" src="https://media.tenor.com/el4CnBiSEyUAAAAM/shore-keeper-shorekeeper-eating.gif" alt="Image" class="my-image">


   <img accesskey="
" src="https://media.tenor.com/frhCxo6PhVEAAAAM/phoebe-wuwa.giff" alt="Image" class="my-Image">


   <img accesskey="
" src="https://media.tenor.com/KABxYqmd0ywAAAA1/wuwa-carlotta.webp" alt="Image" class="my-iImage">


   <img accesskey="
" src="https://media.tenor.com/42R4JLGBrOEAAAAM/carlotta-carlotta-shocked.giff" alt="Image" class="my-iIImage">

 
         <div class=" test1"> 
         <img src="https://media1.tenor.com/m/KdIR-Rgzb5wAAAAd/blue-archive-misono-mika.gif" alt="flip affects">
    </div>
    <h1>Image Hover Effects</h1>
    <div class="container">
        <div class="image-box zoom">
            <p>Zoom Effect</p>
            <img src="https://media1.tenor.com/m/KdIR-Rgzb5wAAAAd/blue-archive-misono-mika.gif" alt="Zoom Effect">
        </div>
        <div class="image-box fade">
            <p>Fade In Effect</p>
            <img src="https://media1.tenor.com/m/KdIR-Rgzb5wAAAAd/blue-archive-misono-mika.gif" alt="Fade Effect">
        </div>
        <div class="image-box rotate">
            <p>Rotate Effect</p>
            <img src="https://media1.tenor.com/m/KdIR-Rgzb5wAAAAd/blue-archive-misono-mika.gif" alt="Rotate Effect">
        </div>
        <div class="image-box slide-up">
            <p>Slide-Up Effect</p>
            <img src="https://media1.tenor.com/m/KdIR-Rgzb5wAAAAd/blue-archive-misono-mika.gif" alt="Slide-Up Effect">
        </div>
        <div class="image-box shake">
            <p>Shake Effect</p>
            <img src="https://media1.tenor.com/m/KdIR-Rgzb5wAAAAd/blue-archive-misono-mika.gif" alt="Shake Effect">
        </div>
        <div class="image-box flip">
            <p>Flip Effect</p>
            <img src="https://media1.tenor.com/m/KdIR-Rgzb5wAAAAd/blue-archive-misono-mika.gif" alt="Flip Effect">
        </div>
        <div class="image-box glow">
            <p>Glowing Border</p>
            <img src="https://media1.tenor.com/m/KdIR-Rgzb5wAAAAd/blue-archive-misono-mika.gif" alt="Glowing Border Effect">
        </div>
    </div>


     

    <main>

    </main>


    <footer>

        <p>
            <strong>
                &copy; 2026 WalangBrownout. All rights reserved BUAHAHAHAHAHAHHAHHAHAHAHA
                .
            </strong>
        </p>

    </footer>


</body>

</html>

<?php

?>