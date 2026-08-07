<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/mailer.php';


// ==========================================
// ALREADY LOGGED IN
// ==========================================

if (!empty($_SESSION['logged_in']) && !empty($_SESSION['role'])) {
    redirect_to_dashboard($_SESSION['role']);
}


// Error starts EMPTY every page load
$error = '';


// ==========================================
// RESET PENDING LOGIN
// ==========================================

if (
    isset($_GET['action']) &&
    $_GET['action'] === 'reset'
) {

    unset(
        $_SESSION['pending_user'],
        $_SESSION['otp_code'],
        $_SESSION['otp_expiry']
    );

    header('Location: login.php');
    exit();
}


// ==========================================
// ONLY RUN LOGIN WHEN FORM IS SUBMITTED
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');


    // ------------------------------------------
    // EMPTY FIELDS
    // ------------------------------------------

    if ($email === '' || $password === '') {

        $error = 'Please enter your email and password.';

    }

    // ------------------------------------------
    // EMAIL FORMAT
    // ------------------------------------------

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    }

    // ------------------------------------------
    // DATABASE CHECK
    // ------------------------------------------

    elseif (!$pdo instanceof PDO) {

        $error = 'Database connection is unavailable.';

    }

    else {

        try {

            // ==========================================
            // FIND USER IN WBO_USERS
            // ==========================================

            $stmt = $pdo->prepare(
                "SELECT
                    user_id,
                    name,
                    email,
                    password_hash,
                    role
                FROM WBO_Users
                WHERE email = ?
                LIMIT 1"
            );

            $stmt->execute([$email]);

            $user = $stmt->fetch();


            // ==========================================
            // CHECK EMAIL + PASSWORD
            // ==========================================
            //
            // For your current project:
            // password_hash contains plain passwords
            // such as:
            //
            // warehouse123
            // admin123
            // etc.
            //
            // ==========================================

            if (
                $user &&
                $password === $user['password_hash']
            ) {

                // ======================================
                // GENERATE OTP
                // ======================================

                $otp = (string) random_int(
                    100000,
                    999999
                );


                // ======================================
                // SAVE PENDING USER
                // ======================================

                $_SESSION['pending_user'] = [

                    'id' => (int) $user['user_id'],

                    'name' => $user['name'],

                    'email' => $user['email'],

                    'role' => normalize_role(
                        $user['role']
                    )

                ];


                // Save generated OTP
                $_SESSION['otp_code'] = $otp;


                // OTP expires after 5 minutes
                $_SESSION['otp_expiry'] =
                    time() + 300;


                // ======================================
                // SEND OTP EMAIL
                // ======================================

                $sent = send_otp_email(
                    $user['email'],
                    $user['name'],
                    $otp
                );


                // ======================================
                // OTP SENT
                // ======================================

                if ($sent) {

                    header('Location: otp.php');
                    exit();

                }


                // ======================================
                // EMAIL FAILED
                // ======================================

                else {

                    unset(
                        $_SESSION['pending_user'],
                        $_SESSION['otp_code'],
                        $_SESSION['otp_expiry']
                    );

                    $error =
                        'Unable to send OTP to your registered email address.';
                }

            }


            // ==========================================
            // WRONG LOGIN
            // ==========================================

            else {

                $error = 'Invalid email or password.';

            }

        }

        catch (PDOException $e) {

            error_log(
                'Login database error: ' .
                $e->getMessage()
            );

            $error =
                'A database error occurred. Please try again.';
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
        WalangBrownout - Login
    </title>

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
            padding: 10px;
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


<header>

    <div
        class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between"
    >

        <div class="flex items-center space-x-3">

            <div>

                <span
                    class="font-bold text-gray-700 text-[11px] uppercase tracking-[0.18em]"
                >
                    Republic of the Philippines
                </span>

                <div
                    class="text-blue-700 text-lg font-extrabold"
                >
                    WALANG BROWN OUT
                </div>

            </div>

        </div>

    </div>


    <nav></nav>

</header>



<main
    class="max-w-md mx-auto mt-16 bg-white shadow-lg rounded-xl p-8"
>


    <div class="text-center mb-6">

        <h2
            class="text-2xl font-bold text-gray-800"
        >
            Welcome Back
        </h2>

        <p
            class="text-xs text-gray-500 mt-1"
        >
            Sign in to access your account portal
        </p>

    </div>



    <!--
        ERROR ONLY SHOWS AFTER FORM SUBMISSION
    -->

    <?php if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        !empty($error)
    ): ?>

        <div
            class="bg-red-50 border border-red-200 text-red-600 text-xs p-3 rounded-lg mb-4"
        >

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>



    <form
        action="login.php"
        method="POST"
        class="space-y-4"
    >


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

                value="<?=
                    htmlspecialchars(
                        $_POST['email'] ?? ''
                    )
                ?>"

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



        <button
            type="submit"

            class="w-full sss-blue hover:bg-blue-800 text-white font-bold py-2.5 rounded-lg text-xs transition shadow"
        >

            Continue to OTP

        </button>


    </form>


</main>



<footer class="mt-10">

    <p>

        <strong>
            &copy; 2026 WalangBrownout.
            All rights reserved.
        </strong>

    </p>

</footer>


</body>

</html>