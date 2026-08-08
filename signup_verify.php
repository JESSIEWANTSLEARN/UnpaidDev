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
// MAKE SURE USER CAME FROM SIGNUP
// ==========================================

if (
    !isset($_SESSION['signup_pending_user']) ||
    !isset($_SESSION['signup_otp_code'])
) {

    header('Location: signup.php');
    exit();
}


$error = '';
$success = '';



// ==========================================
// DEFAULT RESEND VALUES
// ==========================================

$_SESSION['signup_otp_resend_count'] =
    $_SESSION['signup_otp_resend_count'] ?? 0;

$_SESSION['signup_otp_last_sent'] =
    $_SESSION['signup_otp_last_sent'] ?? time();

// ==========================================
// HANDLE FORM
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action =
        $_POST['action'] ?? 'verify';


    // ======================================
    // RESEND OTP
    // ======================================

    if ($action === 'resend') {

        $resendCount =
            (int) $_SESSION['signup_otp_resend_count'];


        // Maximum 2 resends
        if ($resendCount >= 2) {

            $error =
                'You have reached the maximum of 2 OTP resends.';
        }

        else {

            // ==================================
            // 30 SECOND COOLDOWN
            // ==================================

            $secondsPassed =
                time() -
                (int) $_SESSION['signup_otp_last_sent'];


            if ($secondsPassed < 30) {

                $secondsRemaining =
                    30 - $secondsPassed;

                $error =
                    "Please wait {$secondsRemaining} seconds before requesting another OTP.";
            }

            else {

                // ==================================
                // GENERATE NEW OTP
                // ==================================

                $newOtp =
                    (string) random_int(
                        100000,
                        999999
                    );


                $pendingUser =
                    $_SESSION['signup_pending_user'];


                // ==================================
                // SEND NEW OTP
                // ==================================

                $sent =
                    send_otp_email(
                        $pendingUser['email'],
                        $pendingUser['name'],
                        $newOtp
                    );


                if ($sent) {

                    // Replace previous OTP
                    $_SESSION['signup_otp_code'] =
                        $newOtp;


                    // New OTP expires in 5 minutes
                    $_SESSION['signup_otp_expiry'] =
                        time() + 300;


                    // Increase resend count
                    $_SESSION['signup_otp_resend_count']++;


                    // Reset cooldown
                    $_SESSION['signup_otp_last_sent'] =
                        time();


                    $remaining =
                        2 -
                        $_SESSION['signup_otp_resend_count'];


                    $success =
                        "A new OTP has been sent. Resends remaining: {$remaining}.";
                }

                else {

                    $error =
                        'Unable to resend the OTP. Please try again.';
                }
            }
        }
    }


    // ======================================
    // VERIFY OTP
    // ======================================

    elseif ($action === 'verify') {

        $enteredOtp =
            trim($_POST['otp'] ?? '');


        // ==================================
        // EMPTY OTP
        // ==================================

        if ($enteredOtp === '') {

            $error =
                'Please enter the OTP code.';
        }


        // ==================================
        // OTP FORMAT
        // ==================================

        elseif (
            !preg_match(
                '/^[0-9]{6}$/',
                $enteredOtp
            )
        ) {

            $error =
                'OTP must contain exactly 6 digits.';
        }


        // ==================================
        // OTP EXPIRED
        // ==================================

        elseif (
            !isset($_SESSION['signup_otp_expiry']) ||
            time() > $_SESSION['signup_otp_expiry']
        ) {

            $error =
                'Your OTP has expired. Please request a new OTP.';
        }


        // ==================================
        // OTP CORRECT
        // ==================================

        elseif (
            hash_equals(
                (string) $_SESSION['signup_otp_code'],
                (string) $enteredOtp
            )
        ) {

            try {

                $pendingUser =
                    $_SESSION['signup_pending_user'];

                $userId =
                    (int) $pendingUser['id'];


                // ==================================
                // ACTIVATE ACCOUNT
                // ==================================

                $stmt = $pdo->prepare(
                    "UPDATE WBO_Users
                    SET
                        account_status = 'active',
                        email_verified_at = NOW()
                    WHERE user_id = ?"
                );


                $stmt->execute([
                    $userId
                ]);


                // ==================================
                // AUDIT LOG
                // ==================================

                log_activity(
                    $pdo,
                    $userId,
                    'ACCOUNT_VERIFIED',
                    'User successfully verified their email and activated their account'
                );


                // ==================================
                // PROTECT SESSION
                // ==================================

                session_regenerate_id(true);


                // ==================================
                // REMOVE SIGNUP OTP DATA
                // ==================================

                unset(
                    $_SESSION['signup_pending_user'],
                    $_SESSION['signup_otp_code'],
                    $_SESSION['signup_otp_expiry'],
                    $_SESSION['signup_otp_resend_count'],
                    $_SESSION['signup_otp_last_sent']
                );


                // ==================================
                // GO TO LOGIN
                // ==================================

                header(
                    'Location: login.php'
                );

                exit();

            }
            catch (PDOException $e) {

                error_log(
                    'Signup verification database error: ' .
                    $e->getMessage()
                );
                $error =
                    'Unable to activate your account. Please try again.';
            }
        }
        // ==================================
        // WRONG OTP
        // ==================================

        else {

            $error =
                'Invalid OTP code. Please check your email and try again.';
        }
    }
}

// ==========================================
// EMAIL DISPLAY
// ==========================================

$userEmail =
    $_SESSION['signup_pending_user']['email']
    ?? '';

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
        WalangBrownout - Verify Registration
    </title>

    <link
        rel="icon"
        type="image/png"
        href="image/Logo.png"
    >

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
            height: 20px;
        }


        footer {
            text-align: center;
        }


        .sss-blue {
            background-color: #0E5BA8;
        }

    </style>

</head>


<body class="min-h-screen flex flex-col">


<!-- ==========================================
     HEADER
========================================== -->

<header class="relative z-10 border-b bg-white">

    <div
        class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between"
    >

        <div class="flex items-center space-x-3">

            <img
                src="https://img.sanishtech.com/u/6559c6ed2b30023d94b79a0932f09814.png"
                alt="Walang Brown Out Logo"
                width="45"
                height="45"
            >

            <div class="hidden sm:block leading-tight">

                <span
                    class="font-bold text-gray-700 text-[11px] uppercase tracking-[0.18em]"
                >

                    Republic of the Philippines

                </span>

                <div
                    class="text-lg font-extrabold text-blue-700"
                >

                    WALANG BROWN OUT

                </div>

            </div>

        </div>

    </div>

    <nav></nav>

</header>



<!-- ==========================================
     OTP VERIFICATION
========================================== -->

<main
    class="w-full max-w-md mx-auto mt-16 mb-16 bg-white shadow-lg rounded-xl p-8"
>


    <div class="text-center mb-6">

        <h1
            class="text-2xl font-bold text-gray-800"
        >
            Verify Your Email
        </h1>


        <p
            class="text-xs text-gray-500 mt-2"
        >
            We sent a 6-digit verification code to
        </p>


        <p
            class="text-sm font-semibold text-blue-700 mt-1"
        >

            <?= htmlspecialchars(
                $userEmail,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </p>

    </div>



    <!-- ======================================
         ERROR
    ====================================== -->

    <?php if ($error !== ''): ?>

        <div
            class="bg-red-50 border border-red-200 text-red-600 text-xs rounded-lg p-3 mb-4"
        >

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>



    <!-- ======================================
         SUCCESS
    ====================================== -->

    <?php if ($success !== ''): ?>

        <div
            class="bg-green-50 border border-green-200 text-green-700 text-xs rounded-lg p-3 mb-4"
        >

            <?= htmlspecialchars(
                $success,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>



    <!-- ======================================
         VERIFY FORM
    ====================================== -->

    <form
        action="signup_verify.php"
        method="POST"
        class="space-y-4"
    >

        <input
            type="hidden"
            name="action"
            value="verify"
        >


        <div>

            <label
                class="block text-xs font-bold text-gray-700 mb-1"
            >
                6-Digit OTP
            </label>


            <input
                type="text"
                name="otp"
                required
                maxlength="6"
                inputmode="numeric"
                pattern="[0-9]{6}"
                autocomplete="one-time-code"
                placeholder="123456"

                class="w-full text-center text-2xl tracking-[0.35em] font-bold px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            >

        </div>


        <button
            type="submit"
            class="w-full sss-blue hover:bg-blue-800 text-white font-bold py-2.5 rounded-lg transition"
        >

            Verify Account

        </button>

    </form>



    <!-- ======================================
         RESEND FORM
    ====================================== -->

    <form
        action="signup_verify.php"
        method="POST"
        class="mt-4"
    >

        <input
            type="hidden"
            name="action"
            value="resend"
        >


        <button
            type="submit"
            class="w-full border border-blue-600 text-blue-600 hover:bg-blue-50 font-bold py-2.5 rounded-lg transition"
        >

            Resend OTP

        </button>

    </form>


    <p
        class="text-center text-xs text-gray-500 mt-4"
    >

        Maximum of 2 OTP resends.

    </p>


</main>



<!-- ==========================================
     FOOTER
========================================== -->

<footer
    class="mt-auto border-t border-slate-200 bg-white"
>

    <div
        class="max-w-7xl mx-auto px-6 py-6 text-center text-slate-600 text-sm"
    >

        <strong>
            &copy; 2026 WalangBrownOut.
        </strong>

        All rights reserved.

    </div>

</footer>


</body>

</html>