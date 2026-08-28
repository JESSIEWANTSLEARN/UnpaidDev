<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WBOUser;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(
        Request $request,
        OtpService $otpService
    ) {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim($validated['email']));

        $user = WBOUser::where('email', $email)->first();

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password_hash
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if ($user->account_status === 'disabled') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Your account has been disabled. Please contact support.',
            ], 403);
        }

        // A fresh authentication attempt must not inherit a previous
        // authenticated identity or stale OTP flow.
        $this->clearAuthenticatedUser($request);
        $this->clearLoginOtp($request);

        /*
        |--------------------------------------------------------------------------
        | Recover an unfinished signup
        |--------------------------------------------------------------------------
        |
        | Previously, leaving /signup-verify created a dead end:
        | - the email already existed in WBO_Users
        | - login rejected pending_verification accounts
        | - the old browser session might no longer contain the signup OTP
        |
        | A correct email + password now recreates the signup verification flow.
        |
        */

        if ($user->account_status === 'pending_verification') {
            $this->clearSignupOtp($request);

            $otp = $otpService->generate();

            try {
                $otpService->send(
                    $user,
                    $otp,
                    'signup'
                );
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Unable to send a new account verification code.',
                ], 500);
            }

            $request->session()->put([
                'signup_pending_user' => $this->pendingUser($user),
                'signup_otp_hash' => $otpService->hash($otp),
                'signup_otp_expiry' =>
                    $otpService->expiryTimestamp(),
                'signup_otp_resend_count' => 0,
                'signup_otp_attempt_count' => 0,
                'signup_otp_last_sent' => now()->timestamp,
            ]);

            return response()->json([
                'success' => true,
                'message' =>
                    'Your account still needs verification. A new code has been sent.',
                'redirect' => '/signup-verify',
                'verification' => 'signup',
                'email' => $user->email,
                'otp_policy' => $otpService->publicPolicy(),
            ]);
        }

        if ($user->account_status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is currently unavailable.',
            ], 403);
        }

        $this->clearSignupOtp($request);

        $otp = $otpService->generate();

        try {
            $otpService->send(
                $user,
                $otp,
                'login'
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to send OTP to your registered email address.',
            ], 500);
        }

        $request->session()->put([
            'pending_user' => $this->pendingUser($user),
            'otp_hash' => $otpService->hash($otp),
            'otp_expiry' => $otpService->expiryTimestamp(),
            'otp_resend_count' => 0,
            'otp_attempt_count' => 0,
            'otp_last_sent' => now()->timestamp,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP has been sent to your email.',
            'redirect' => '/login-otp',
            'verification' => 'login',
            'email' => $user->email,
            'otp_policy' => $otpService->publicPolicy(),
        ]);
    }

    private function pendingUser(WBOUser $user): array
    {
        return [
            'id' => (int) $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }

    private function clearAuthenticatedUser(Request $request): void
    {
        $request->session()->forget([
            'logged_in',
            'user_id',
            'name',
            'email',
            'role',
            'last_activity',
        ]);
    }

    private function clearLoginOtp(Request $request): void
    {
        $request->session()->forget([
            'pending_user',
            'otp_code',
            'otp_hash',
            'otp_expiry',
            'otp_resend_count',
            'otp_attempt_count',
            'otp_last_sent',
        ]);
    }

    private function clearSignupOtp(Request $request): void
    {
        $request->session()->forget([
            'signup_pending_user',
            'signup_otp_code',
            'signup_otp_hash',
            'signup_otp_expiry',
            'signup_otp_resend_count',
            'signup_otp_attempt_count',
            'signup_otp_last_sent',
        ]);
    }
}