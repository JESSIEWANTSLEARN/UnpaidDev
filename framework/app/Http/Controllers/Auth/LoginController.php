<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WBOUser;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // ==========================================
        // VALIDATE LOGIN INPUT
        // ==========================================

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);


        // ==========================================
        // FIND USER
        // ==========================================

        $user = WBOUser::where('email', $request->email)->first();


        // ==========================================
        // CHECK EMAIL + PASSWORD
        // ==========================================

        if (
            !$user ||
            !Hash::check($request->password, $user->password_hash)
        ) {

            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }


        // ==========================================
        // CHECK ACCOUNT STATUS
        // ==========================================

        if ($user->account_status === 'disabled') {

            return response()->json([
                'success' => false,
                'message' => 'Your account has been disabled. Please contact support.',
            ], 403);
        }


        if ($user->account_status === 'pending_verification') {

            return response()->json([
                'success' => false,
                'message' => 'Your account is not yet verified. Please complete email verification.',
            ], 403);
        }


        if ($user->account_status !== 'active') {

            return response()->json([
                'success' => false,
                'message' => 'Your account is currently unavailable.',
            ], 403);
        }


        // ==========================================
        // GENERATE OTP
        // ==========================================

        $otp = (string) random_int(100000, 999999);


        // ==========================================
        // SAVE PENDING LOGIN SESSION
        // ==========================================

        session([
            'pending_user' => [
                'id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],

            'otp_code' => $otp,

            // OTP expires after 5 minutes
            'otp_expiry' => now()->addMinutes(5)->timestamp,

            // No resends used yet
            'otp_resend_count' => 0,

            // Used for 30-second cooldown
            'otp_last_sent' => now()->timestamp,
        ]);


        // ==========================================
        // SEND OTP EMAIL
        // ==========================================

        try {

            Mail::to($user->email)->send(
                new OtpMail(
                    $user->name,
                    $otp
                )
            );

        } catch (\Throwable $e) {

            // Remove pending login data if sending fails
            session()->forget([
                'pending_user',
                'otp_code',
                'otp_expiry',
                'otp_resend_count',
                'otp_last_sent',
            ]);

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send OTP to your registered email address.',
            ], 500);
        }


        // ==========================================
        // LOGIN STEP 1 SUCCESSFUL
        // ==========================================

        return response()->json([
            'success' => true,
            'message' => 'OTP has been sent to your email.',
            'redirect' => '/login-otp',
        ]);
    }
}