<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/mailer.php';


// ==========================================
// MAKE SURE USER CAME FROM LOGIN
// ==========================================

if (
    !isset($_SESSION['pending_user']) ||
    !isset($_SESSION['otp_code'])
) {

    header('Location: login.php');
    exit();
}


$error = '';
$success = '';


// ==========================================
// DEFAULT RESEND VALUES
// ==========================================

$_SESSION['otp_resend_count'] =
    $_SESSION['otp_resend_count'] ?? 0;

$_SESSION['otp_last_sent'] =
    $_SESSION['otp_last_sent'] ?? time();


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
            (int) $_SESSION['otp_resend_count'];


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
                (int) $_SESSION['otp_last_sent'];


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
                    $_SESSION['pending_user'];


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

                    // Replace old OTP
                    $_SESSION['otp_code'] =
                        $newOtp;


                    // Give new OTP another 5 minutes
                    $_SESSION['otp_expiry'] =
                        time() + 300;


                    // Increase resend count
                    $_SESSION['otp_resend_count']++;


                    // Reset cooldown
                    $_SESSION['otp_last_sent'] =
                        time();


                    $remaining =
                        2 -
                        $_SESSION['otp_resend_count'];


                    $success =
                        "A new OTP has been sent. Resends remaining: {$remaining}.";
                }

                else {

                    $error =
                        'Unable to resend OTP. Please try again.';
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


        if ($enteredOtp === '') {

            $error =
                'Please enter the OTP code.';
        }


        // ==================================
        // OTP EXPIRED
        // ==================================

        elseif (
            isset($_SESSION['otp_expiry']) &&
            time() > $_SESSION['otp_expiry']
        ) {

            $error =
                'Your OTP has expired. Please request a new OTP.';
        }


        // ==================================
        // OTP CORRECT
        // ==================================

        elseif (
            hash_equals(
                (string) $_SESSION['otp_code'],
                (string) $enteredOtp
            )
        ) {

            $_SESSION['logged_in'] =
                true;


            $_SESSION['user_id'] =
                $_SESSION['pending_user']['id'];


            $_SESSION['name'] =
                $_SESSION['pending_user']['name'];


            $_SESSION['email'] =
                $_SESSION['pending_user']['email'];


            $_SESSION['role'] =
                normalize_role(
                    $_SESSION['pending_user']['role']
                );


            // ==================================
            // REMOVE TEMPORARY OTP DATA
            // ==================================

            unset(
                $_SESSION['pending_user'],
                $_SESSION['otp_code'],
                $_SESSION['otp_expiry'],
                $_SESSION['otp_resend_count'],
                $_SESSION['otp_last_sent']
            );


            redirect_to_dashboard(
                $_SESSION['role']
            );

            exit();
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


$userEmail =
    $_SESSION['pending_user']['email']
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
        WalangBrownout - Verify OTP
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

    <nav></nav>

</header>



<main
    class="max-w-md mx-auto mt-16 bg-white shadow-lg rounded-xl p-8"
>


    <div class="text-center mb-6">

        <div class="text-4xl mb-3">
            📩
        </div>

        <h2
            class="text-2xl font-bold text-gray-800"
        >
            Enter OTP Code
        </h2>

        <p
            class="text-xs text-gray-500 mt-2"
        >
            We sent a 6-digit verification code to:
        </p>

        <p
            class="text-sm font-bold text-blue-700 mt-1"
        >
            <?= htmlspecialchars(
                $_SESSION['pending_user']['email']
            ) ?>
        </p>

    </div>



    <?php if (!empty($error)): ?>

        <div
            class="bg-red-50 border border-red-200 text-red-600 text-xs p-3 rounded-lg mb-4"
        >

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>



    <form
        action="otp.php"
        method="POST"
        class="space-y-4"
    >


        <div>

            <label
                class="block text-xs font-bold text-gray-700 mb-2 text-center"
            >
                6-Digit Verification Code
            </label>


            <input
                type="text"
                name="otp"
                maxlength="6"
                minlength="6"
                pattern="[0-9]{6}"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
                autofocus
                placeholder="123456"
                class="w-full text-center text-2xl tracking-[0.4em] font-bold px-4 py-3 border-2 border-blue-400 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
            >

        </div>



        <button
            type="submit"
            class="w-full sss-blue hover:bg-blue-800 text-white font-bold py-3 rounded-xl text-sm transition shadow"
        >
            Verify & Sign In
        </button>


    </form>



    <div class="text-center mt-5">

        <a
            href="login.php?action=reset"
            class="text-xs text-red-500 hover:underline"
        >
            Cancel / Use Different Account
        </a>

    </div>


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