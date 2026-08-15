<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class LoginOtpController extends Controller
{
    // ==========================================
    // VERIFY LOGIN OTP
    // ==========================================

    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);


        // User must come from successful login
        $pendingUser = session('pending_user');
        $storedOtp = session('otp_code');
        $otpExpiry = session('otp_expiry');


        if (!$pendingUser || !$storedOtp) {

            return response()->json([
                'success' => false,
                'message' => 'Your login session has expired. Please log in again.',
                'redirect' => '/login',
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
                'message' => 'Your OTP has expired. Please request a new OTP.',
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
                'message' => 'Invalid OTP code. Please check your email and try again.',
            ], 422);
        }


        // ==========================================
        // PROTECT AGAINST SESSION FIXATION
        // ==========================================

        $request->session()->regenerate();


        // ==========================================
        // CREATE LOGGED-IN SESSION
        // ==========================================

        session([
            'logged_in' => true,

            'user_id' =>
                (int) $pendingUser['id'],

            'name' =>
                $pendingUser['name'],

            'email' =>
                $pendingUser['email'],

            'role' =>
                $pendingUser['role'],

            'last_activity' =>
                time(),
        ]);


        // ==========================================
        // AUDIT LOG
        // ==========================================

        try {

            DB::table('WBO_AuditLogs')->insert([
                'user_id' =>
                    (int) $pendingUser['id'],

                'action' =>
                    'LOGIN',

                'description' =>
                    'User successfully logged in',

                'ip_address' =>
                    $request->ip(),
            ]);

        } catch (\Throwable $e) {

            // Audit failure must not block login
            report($e);
        }


        // ==========================================
        // REMOVE TEMPORARY OTP DATA
        // ==========================================

        session()->forget([
            'pending_user',
            'otp_code',
            'otp_expiry',
            'otp_resend_count',
            'otp_last_sent',
        ]);


        // ==========================================
        // ROLE DASHBOARD
        // ==========================================

        $redirect = $this->dashboardForRole(
            session('role')
        );


        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'redirect' => $redirect,
        ]);
    }


    // ==========================================
    // RESEND OTP
    // ==========================================

    public function resend(Request $request)
    {
        $pendingUser =
            session('pending_user');


        if (!$pendingUser) {

            return response()->json([
                'success' => false,
                'message' => 'Your login session has expired. Please log in again.',
                'redirect' => '/login',
            ], 401);
        }


        // ==========================================
        // MAXIMUM 2 RESENDS
        // ==========================================

        $resendCount =
            (int) session(
                'otp_resend_count',
                0
            );


        if ($resendCount >= 2) {

            return response()->json([
                'success' => false,
                'message' => 'You have reached the maximum of 2 OTP resends.',
            ], 429);
        }


        // ==========================================
        // 30 SECOND COOLDOWN
        // ==========================================

        $lastSent =
            (int) session(
                'otp_last_sent',
                0
            );


        $secondsPassed =
            time() - $lastSent;


        if ($secondsPassed < 30) {

            $secondsRemaining =
                30 - $secondsPassed;


            return response()->json([
                'success' => false,
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

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to resend OTP. Please try again.',
            ], 500);
        }


        // ==========================================
        // SAVE NEW OTP
        // ==========================================

        session([
            'otp_code' =>
                $newOtp,

            'otp_expiry' =>
                time() + 300,

            'otp_resend_count' =>
                $resendCount + 1,

            'otp_last_sent' =>
                time(),
        ]);


        $remaining =
            2 - ($resendCount + 1);


        return response()->json([
            'success' => true,

            'message' =>
                "A new OTP has been sent. Resends remaining: {$remaining}.",

            'resends_remaining' =>
                $remaining,
        ]);
    }


    // ==========================================
    // DASHBOARD BASED ON ROLE
    // ==========================================

    private function dashboardForRole(
        ?string $role
    ): string {

        return match ($role) {

            'super_admin' =>
                '/super-admin',

            'Operations_Manager' =>
                '/operations-manager',

            'Purchasing_Manager' =>
                '/purchasing-manager',

            'Warehouse_Admin' =>
                '/warehouse-admin',

            'Sales_Manager' =>
                '/sales-manager',

            'Purchasing_Staff' =>
                '/purchasing-staff',

            'Inventory_Controller' =>
                '/inventory-controller',

            'Sales_Staff' =>
                '/sales-staff',

            'User_Admin' =>
                '/user-admin',

            default =>
                '/user',
        };
    }
}