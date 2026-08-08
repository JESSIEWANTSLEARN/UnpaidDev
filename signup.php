<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/audit.php';


// ==========================================
// DATABASE CHECK
// ==========================================

if (
    !isset($pdo) ||
    !($pdo instanceof PDO)
) {
    die('Database connection is unavailable.');
}

// ==========================================
// ALREADY LOGGED IN
// ==========================================

if (
    !empty($_SESSION['logged_in']) &&
    !empty($_SESSION['role'])
) {

    redirect_to_dashboard(
        $_SESSION['role']
    );
}
// ==========================================
// DEFAULT VALUES
// ==========================================

$error = '';

$name = '';
$email = '';
$contactNumber = '';

// ==========================================
// HANDLE SIGNUP
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name =
        trim($_POST['name'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $contactNumber =
        trim($_POST['contact_number'] ?? '');

    // Do not trim passwords
    $password =
        $_POST['password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    // ======================================
    // EMPTY FIELDS
    // ======================================

    if (
        $name === '' ||
        $email === '' ||
        $contactNumber === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {

        $error =
            'Please complete all required fields.';
    }


    // ======================================
    // EMAIL VALIDATION
    // ======================================

    elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';
    }

    // ======================================
    // CONTACT NUMBER VALIDATION
    // ======================================

    elseif (
        !preg_match(
            '/^[0-9+\-\s]{7,20}$/',
            $contactNumber
        )
    ) {

        $error =
            'Please enter a valid contact number.';
    }

    // ======================================
    // PASSWORD LENGTH
    // ======================================

    elseif (strlen($password) < 6) {

        $error =
            'Password must be at least 6 characters long.';
    }


    // ======================================
    // CONFIRM PASSWORD
    // ======================================

    elseif ($password !== $confirmPassword) {

        $error =
            'Passwords do not match.';
    }

    else {

        try {

            // ==================================
            // CHECK IF EMAIL ALREADY EXISTS
            // ==================================

            $stmt = $pdo->prepare(
                "SELECT user_id
                FROM WBO_Users
                WHERE email = ?
                LIMIT 1"
            );

            $stmt->execute([
                $email
            ]);

            $existingUser =
                $stmt->fetch();


            if ($existingUser) {

                $error =
                    'This email address is already registered.';
            }


            else {

                // ==================================
                // HASH PASSWORD
                // ==================================

                $passwordHash =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                // ==================================
                // PUBLIC USERS ARE ALWAYS CLIENTS
                // ==================================

                $role =
                    'System_User';

                $accountStatus =
                    'pending_verification';


                // ==================================
                // START DATABASE TRANSACTION
                // ==================================

                $pdo->beginTransaction();


                // ==================================
                // CREATE ACCOUNT
                // ==================================

                $stmt = $pdo->prepare(
                    "INSERT INTO WBO_Users
                    (
                        name,
                        email,
                        contact_number,
                        password_hash,
                        role,
                        account_status,
                        email_verified_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        NULL
                    )"
                );


                $stmt->execute([

                    $name,

                    $email,

                    $contactNumber,

                    $passwordHash,

                    $role,

                    $accountStatus

                ]);


                $userId =
                    (int) $pdo->lastInsertId();


                // ==================================
                // GENERATE SIGNUP OTP
                // ==================================

                $otp =
                    (string) random_int(
                        100000,
                        999999
                    );


                // ==================================
                // SAVE TEMPORARY SIGNUP SESSION
                // ==================================

                $_SESSION['signup_pending_user'] = [

                    'id' =>
                        $userId,

                    'name' =>
                        $name,

                    'email' =>
                        $email,

                    'role' =>
                        $role

                ];


                $_SESSION['signup_otp_code'] =
                    $otp;


                // OTP expires after 5 minutes
                $_SESSION['signup_otp_expiry'] =
                    time() + 300;


                // No resends used yet
                $_SESSION['signup_otp_resend_count'] =
                    0;


                $_SESSION['signup_otp_last_sent'] =
                    time();


                // ==================================
                // SEND VERIFICATION EMAIL
                // ==================================

                $sent =
                    send_otp_email(
                        $email,
                        $name,
                        $otp
                    );


                if ($sent) {

                    // Save account permanently
                    $pdo->commit();


                    // ==================================
                    // RECORD REGISTRATION
                    // ==================================

                    log_activity(
                        $pdo,
                        $userId,
                        'REGISTER',
                        'System user account registered and awaiting email verification'
                    );


                    // ==================================
                    // GO TO SIGNUP OTP PAGE
                    // ==================================

                    header(
                        'Location: signup_verify.php'
                    );

                    exit();
                }


                else {

                    // Email failed, so do not leave
                    // an unusable pending account.

                    if ($pdo->inTransaction()) {

                        $pdo->rollBack();
                    }


                    unset(
                        $_SESSION['signup_pending_user'],
                        $_SESSION['signup_otp_code'],
                        $_SESSION['signup_otp_expiry'],
                        $_SESSION['signup_otp_resend_count'],
                        $_SESSION['signup_otp_last_sent']
                    );


                    $error =
                        'Unable to send the verification OTP. Please try again.';
                }
            }

        }

        catch (PDOException $e) {

            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }


            error_log(
                'Signup database error: ' .
                $e->getMessage()
            );


            $error =
                'Unable to create your account. Please try again.';
        }
    }
}

?>






<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        WalangBrownout - Signup
    </title>

    <link rel="icon" type="image/png" href="image/Logo.png"> <!-- Placeholder for client icon -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #F2F2F2;
            color: #0E5BA8;
            margin: 0;
        }

        header {
            text-align: left;
        }

        header nav {
            background-color: #2C3E50;
            padding: 20px;
            margin-bottom: 20px;
        }

        footer {
            text-align: center;
        }

        .sss-blue {
            background-color: #0E5BA8;
        }

    </style>

</head>


<body>

<!-- Header -->
    <header class="relative z-10 border-b">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">

                <img src="https://img.sanishtech.com/u/6559c6ed2b30023d94b79a0932f09814.png"
                    alt="Walang Brown Out Logo"
                    width="45"
                    height="45">

                <div class="hidden sm:block leading-tight">

                    <span class="font-bold text-gray-700 text-[11px] uppercase tracking-[0.18em]">
                        Republic of the Philippines
                    </span>

                    <div class="text-lg font-extrabold text-blue-700">
                        WALANG BROWN OUT
                    </div>

                </div>

            </div>
        </div>
        
        <nav>
            
        </nav>

    </header>

    <!-- Main Content --> 
    <main class="max-w-md mx-auto mt-16 mb-16 bg-white shadow-lg rounded-xl p-8" >

        <h1 class="text-2xl font-bold text-gray-700 mb-6">
            Create an Account
        </h1>

        <form
            action="signup.php"
            method="POST"
            class="space-y-4"
        >
            <div>

                <label
                    class="block text-xs font-bold text-gray-700 mb-1"
                >
                    Username
                </label>

                <input
                    type="text"
                    name="username"
                    required
                    placeholder="Your_Username"

                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >

            </div>

            <div>

                <label
                    class="block text-xs font-bold text-gray-700 mb-1"
                >
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    required
                    placeholder="name@example.com"

                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >

            </div>

            <div>

                <label
                    class="block text-xs font-bold text-gray-700 mb-1"
                >
                    Contact Number
                </label>

                <input
                    type="Number"
                    name="contact_number"
                    required
                    placeholder="123-456-7890"

                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >

            </div>

            <div>

                <label
                    class="block text-xs font-bold text-gray-700 mb-1"
                >
                    Password
                </label>


                <input
                    type="password"
                    name="password"
                    required
                    placeholder="••••••••"

                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >

            </div>

            <div>

                <label
                    class="block text-xs font-bold text-gray-700 mb-1"
                >
                    Confirm Password
                </label>


                <input
                    type="password"
                    name="confirm_password"
                    required
                    placeholder="••••••••"

                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >

            </div>

            <button
                type="submit"
                class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Create Account
            </button>

            <p class="text-center text-sm text-gray-600 mt-4">
                Already have an account? <a href="login.php" class="text-blue-500 hover:underline">Login here</a>
            </p>
        </form>

    </main>

    <!-- Footer -->
    <footer class="relative z-10 border-t border-slate-200 bg-#F2F2F2/80 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-6 py-6 text-center text-slate-600 text-sm">
            <strong>&copy; 2026 WalangBrownOut.</strong>
            All rights reserved.
        </div>
    </footer>


</body>

</html>