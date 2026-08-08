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
