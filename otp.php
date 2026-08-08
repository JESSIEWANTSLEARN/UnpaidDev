<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/audit.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Database connection is unavailable.');
}
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


        // ==================================
        // EMPTY OTP
        // ==================================

        if ($enteredOtp === '') {

            $error =
                'Please enter the OTP code.';
        }


        // ==================================
        // OTP EXPIRED
        // ==================================

        elseif (
            !isset($_SESSION['otp_expiry']) ||
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

            // Protect against session fixation
            session_regenerate_id(true);


            // ==================================
            // CREATE LOGGED-IN SESSION
            // ==================================

            $_SESSION['logged_in'] =
                true;

            $_SESSION['user_id'] =
                (int) $_SESSION['pending_user']['id'];

            $_SESSION['name'] =
                $_SESSION['pending_user']['name'];

            $_SESSION['email'] =
                $_SESSION['pending_user']['email'];

            $_SESSION['role'] =
                normalize_role(
                    $_SESSION['pending_user']['role']
                );


            // ==================================
            // RECORD SUCCESSFUL LOGIN
            // ==================================

            log_activity(
                $pdo,
                $_SESSION['user_id'],
                'LOGIN',
                'User successfully logged in'
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


            // ==================================
            // REDIRECT TO USER DASHBOARD
            // ==================================

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


// ==========================================
// EMAIL DISPLAYED ON OTP PAGE
// ==========================================

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
        OTP Verification - WalangBrownout
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family: Arial, sans-serif;

            background-color: #F2F2F2;

            color: #333;
        }


        /* =========================================
           HEADER
        ========================================= */

        header {

            background-color: white;

            padding: 20px 30px;

            border-bottom: 15px solid #2C3E50;
        }


        .header-small {

            font-size: 11px;

            font-weight: bold;

            letter-spacing: 2px;

            color: #666;

            text-transform: uppercase;
        }


        .header-title {

            margin-top: 5px;

            color: #0E5BA8;

            font-size: 22px;

            font-weight: bold;
        }


        /* =========================================
           MAIN
        ========================================= */

        main {

            min-height: calc(100vh - 100px);

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 30px 20px;
        }


        /* =========================================
           OTP CONTAINER
        ========================================= */

        .otp-box {

            width: 100%;

            max-width: 430px;

            background-color: white;

            padding: 35px;

            border-radius: 12px;

            box-shadow:
                0 4px 18px
                rgba(0, 0, 0, 0.10);
        }


        .otp-box h2 {

            margin-top: 0;

            margin-bottom: 10px;

            text-align: center;

            font-size: 25px;

            color: #222;
        }


        .description {

            text-align: center;

            font-size: 14px;

            color: #666;

            line-height: 1.6;

            margin-bottom: 25px;
        }


        .email {

            color: #0E5BA8;

            font-weight: bold;
        }


        /* =========================================
           ERROR MESSAGE
        ========================================= */

        .error {

            padding: 12px;

            margin-bottom: 20px;

            background-color: #FFE5E5;

            color: #B30000;

            border-radius: 7px;

            text-align: center;

            font-size: 13px;
        }


        /* =========================================
           SUCCESS MESSAGE
        ========================================= */

        .success {

            padding: 12px;

            margin-bottom: 20px;

            background-color: #E7F8E7;

            color: #197A19;

            border-radius: 7px;

            text-align: center;

            font-size: 13px;
        }


        /* =========================================
           LABEL
        ========================================= */

        label {

            display: block;

            margin-bottom: 7px;

            font-size: 13px;

            font-weight: bold;

            color: #333;
        }


        /* =========================================
           OTP INPUT
        ========================================= */

        input[type="text"] {

            width: 100%;

            padding: 14px;

            border: 1px solid #CCC;

            border-radius: 7px;

            font-size: 22px;

            text-align: center;

            letter-spacing: 8px;
        }


        input[type="text"]:focus {

            outline: none;

            border-color: #0E5BA8;
        }


        /* =========================================
           VERIFY BUTTON
        ========================================= */

        .verify-button {

            width: 100%;

            margin-top: 20px;

            padding: 13px;

            border: none;

            border-radius: 7px;

            background-color: #0E5BA8;

            color: white;

            font-weight: bold;

            cursor: pointer;
        }


        .verify-button:hover {

            background-color: #084A89;
        }


        /* =========================================
           RESEND SECTION
        ========================================= */

        .resend-section {

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #DDD;

            text-align: center;
        }


        .resend-text {

            margin-bottom: 10px;

            color: #777;

            font-size: 13px;
        }


        .resend-button {

            background: none;

            border: none;

            color: #0E5BA8;

            font-weight: bold;

            font-size: 13px;

            cursor: pointer;
        }


        .resend-button:hover {

            text-decoration: underline;
        }


        /* =========================================
           INFO
        ========================================= */

        .otp-info {

            margin-top: 20px;

            text-align: center;

            color: #888;

            font-size: 12px;

            line-height: 1.6;
        }


        /* =========================================
           BACK TO LOGIN
        ========================================= */

        .back {

            margin-top: 20px;

            text-align: center;
        }


        .back a {

            color: #0E5BA8;

            font-size: 13px;

            font-weight: bold;

            text-decoration: none;
        }


        .back a:hover {

            text-decoration: underline;
        }

    </style>

</head>


<body>


    <!-- =========================================
         HEADER
    ========================================== -->

    <header>

        <div class="header-small">

            Republic of the Philippines

        </div>


        <div class="header-title">

            WALANG BROWN OUT

        </div>

    </header>



    <!-- =========================================
         MAIN
    ========================================== -->

    <main>


        <div class="otp-box">


            <h2>
                OTP Verification
            </h2>


            <p class="description">

                We sent a 6-digit verification code to

                <br>

                <span class="email">

                    <?php

                    echo htmlspecialchars(
                        $userEmail,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </span>

            </p>



            <!-- =================================
                 ERROR MESSAGE
            ================================== -->

            <?php if ($error !== ''): ?>


                <div class="error">

                    <?php

                    echo htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </div>


            <?php endif; ?>



            <!-- =================================
                 SUCCESS MESSAGE
            ================================== -->

            <?php if ($success !== ''): ?>


                <div class="success">

                    <?php

                    echo htmlspecialchars(
                        $success,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </div>


            <?php endif; ?>



            <!-- =================================
                 VERIFY OTP FORM
            ================================== -->

            <form
                action="otp.php"
                method="POST"
            >


                <!-- Tell PHP this is VERIFY -->

                <input
                    type="hidden"
                    name="action"
                    value="verify"
                >


                <label for="otp">

                    Enter OTP Code

                </label>


                <input
                    type="text"
                    id="otp"
                    name="otp"
                    maxlength="6"
                    minlength="6"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    placeholder="000000"
                    autocomplete="one-time-code"
                    required
                >


                <button
                    type="submit"
                    class="verify-button"
                >

                    Verify OTP

                </button>


            </form>



            <!-- =================================
                 OTP INFORMATION
            ================================== -->

            <div class="otp-info">

                OTP expires after 5 minutes.

                <br>

                You may resend the OTP
                a maximum of 2 times.

            </div>



            <!-- =================================
                 RESEND OTP
            ================================== -->

            <div class="resend-section">


                <div class="resend-text">

                    Didn't receive the code?

                </div>


                <form
                    action="otp.php"
                    method="POST"
                >


                    <!-- Tell PHP this is RESEND -->

                    <input
                        type="hidden"
                        name="action"
                        value="resend"
                    >


                    <button
                        type="submit"
                        class="resend-button"
                    >

                        Resend OTP

                    </button>


                </form>


            </div>



            <!-- =================================
                 BACK TO LOGIN
            ================================== -->

            <div class="back">


                <a href="login.php?action=reset">

                    ← Back to Login

                </a>


            </div>


        </div>


    </main>


</body>

</html>