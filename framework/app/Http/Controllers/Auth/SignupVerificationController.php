<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\WBOUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SignupVerificationController extends Controller
{
    // ==========================================
    // VERIFY SIGNUP OTP
    // ==========================================

    public function verify(Request $request)
    {
        $request->validate([
            'otp' => [
                'required',
                'digits:6',
            ],
        ]);


        // ==========================================
        // GET SIGNUP SESSION DATA
        // ==========================================

        $pendingUser =
            session('signup_pending_user');

        $storedOtp =
            session('signup_otp_code');

        $otpExpiry =
            session('signup_otp_expiry');


        // ==========================================
        // MAKE SURE USER CAME FROM SIGNUP
        // ==========================================

        if (
            !$pendingUser ||
            !$storedOtp
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Your signup verification session has expired. Please create your account again.',
                'redirect' =>
                    '/signup',
            ], 401);
        }


        // ==========================================
        // CHECK OTP EXPIRATION
        // ==========================================

        if (
            !$otpExpiry ||
            time() > $otpExpiry
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Your OTP has expired. Please request a new OTP.',
            ], 422);
        }


        // ==========================================
        // CHECK OTP
        // ==========================================

        if (
            !hash_equals(
                (string) $storedOtp,
                (string) $request->otp
            )
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Invalid OTP code. Please check your email and try again.',
            ], 422);
        }


        // ==========================================
        // GET USER ID
        // ==========================================

        $userId =
            (int) $pendingUser['id'];


        try {

            DB::beginTransaction();


            // ==========================================
            // ACTIVATE USER ACCOUNT
            // ==========================================

            $user = WBOUser::where(
                'user_id',
                $userId
            )->first();


            if (!$user) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' =>
                        'The account could not be found.',
                    'redirect' =>
                        '/signup',
                ], 404);
            }


            $user->account_status =
                'active';

            $user->email_verified_at =
                now();

            $user->save();


            // ==========================================
            // AUDIT LOG
            // ==========================================

            try {

                DB::table(
                    'WBO_AuditLogs'
                )->insert([

                    'user_id' =>
                        $userId,

                    'action' =>
                        'ACCOUNT_VERIFIED',

                    'description' =>
                        'User successfully verified their email and activated their account',

                    'ip_address' =>
                        $request->ip(),

                ]);

            } catch (\Throwable $e) {

                // Audit failure should not stop
                // account verification
                report($e);
            }


            DB::commit();


            // ==========================================
            // PROTECT AGAINST SESSION FIXATION
            // ==========================================

            $request
                ->session()
                ->regenerate();


            // ==========================================
            // REMOVE SIGNUP OTP SESSION DATA
            // ==========================================

            session()->forget([

                'signup_pending_user',

                'signup_otp_code',

                'signup_otp_expiry',

                'signup_otp_resend_count',

                'signup_otp_last_sent',

            ]);


            // ==========================================
            // SUCCESS
            // ==========================================

            return response()->json([

                'success' =>
                    true,

                'message' =>
                    'Your account has been verified successfully. You may now log in.',

                'redirect' =>
                    '/login',

            ]);

        }

        catch (\Throwable $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to activate your account. Please try again.',
            ], 500);
        }
    }


    // ==========================================
    // RESEND SIGNUP OTP
    // ==========================================

    public function resend(Request $request)
    {
        $pendingUser =
            session('signup_pending_user');


        // ==========================================
        // CHECK SIGNUP SESSION
        // ==========================================

        if (!$pendingUser) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Your signup verification session has expired. Please create your account again.',
                'redirect' =>
                    '/signup',
            ], 401);
        }


        // ==========================================
        // CURRENT RESEND COUNT
        // ==========================================

        $resendCount =
            (int) session(
                'signup_otp_resend_count',
                0
            );


        // ==========================================
        // MAXIMUM 2 RESENDS
        // ==========================================

        if ($resendCount >= 2) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You have reached the maximum of 2 OTP resends.',
            ], 429);
        }


        // ==========================================
        // 30-SECOND COOLDOWN
        // ==========================================

        $lastSent =
            (int) session(
                'signup_otp_last_sent',
                0
            );


        $secondsPassed =
            time() - $lastSent;


        if ($secondsPassed < 30) {

            $secondsRemaining =
                30 - $secondsPassed;


            return response()->json([

                'success' =>
                    false,

                'message' =>
                    "Please wait {$secondsRemaining} seconds before requesting another OTP.",

                'seconds_remaining' =>
                    $secondsRemaining,

            ], 429);
        }


        // ==========================================
        // GENERATE NEW OTP
        // ==========================================

        $newOtp =
            (string) random_int(
                100000,
                999999
            );


        // ==========================================
        // SEND NEW OTP
        // ==========================================

        try {

            Mail::to(
                $pendingUser['email']
            )->send(

                new OtpMail(
                    $newOtp,
                    $pendingUser['name']
                )

            );

        }

        catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to resend the OTP. Please try again.',
            ], 500);
        }


        // ==========================================
        // SAVE NEW OTP
        // ==========================================

        session([

            'signup_otp_code' =>
                $newOtp,

            // New OTP valid for another 5 minutes
            'signup_otp_expiry' =>
                now()->addMinutes(5)->timestamp,

            'signup_otp_resend_count' =>
                $resendCount + 1,

            'signup_otp_last_sent' =>
                now()->timestamp,

        ]);


        $remaining =
            2 - ($resendCount + 1);


        return response()->json([

            'success' =>
                true,

            'message' =>
                "A new OTP has been sent. Resends remaining: {$remaining}.",

            'resends_remaining' =>
                $remaining,

        ]);
    }
}