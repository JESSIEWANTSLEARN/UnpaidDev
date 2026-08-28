<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WBOUser;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SignupVerificationController extends Controller
{
    public function verify(
        Request $request,
        OtpService $otpService
    ) {
        $length = $otpService->length();

        $request->validate([
            'otp' => "required|digits:{$length}",
        ]);

        $pendingUser =
            $request->session()->get('signup_pending_user');

        if (!$pendingUser) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Your verification session has ended. Log in with your email and password to receive a new verification code.',
                'redirect' => '/login',
            ], 401);
        }

        $storedHash =
            $request->session()->get('signup_otp_hash');

        $otpExpiry =
            $request->session()->get('signup_otp_expiry');

        if (!$storedHash) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This OTP is no longer valid. Please request a new OTP.',
                'can_resend' => true,
                'otp_policy' => $otpService->publicPolicy(),
            ], 422);
        }

        if (
            !$otpExpiry ||
            now()->timestamp > (int) $otpExpiry
        ) {
            $request->session()->forget([
                'signup_otp_hash',
                'signup_otp_expiry',
                'signup_otp_attempt_count',
            ]);

            return response()->json([
                'success' => false,
                'message' =>
                    'Your OTP has expired. Please request a new OTP.',
                'can_resend' => true,
                'otp_policy' => $otpService->publicPolicy(),
            ], 422);
        }

        if (
            !$otpService->matches(
                (string) $request->otp,
                (string) $storedHash
            )
        ) {
            $attempts =
                (int) $request->session()->get(
                    'signup_otp_attempt_count',
                    0
                ) + 1;

            $request->session()->put(
                'signup_otp_attempt_count',
                $attempts
            );

            $remaining = max(
                0,
                $otpService->maxAttempts() - $attempts
            );

            if ($remaining === 0) {
                $request->session()->forget([
                    'signup_otp_hash',
                    'signup_otp_expiry',
                ]);

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Too many incorrect attempts. Request a new OTP.',
                    'attempts_remaining' => 0,
                    'can_resend' => true,
                    'otp_policy' => $otpService->publicPolicy(),
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' =>
                    "Invalid OTP code. Attempts remaining: {$remaining}.",
                'attempts_remaining' => $remaining,
                'otp_policy' => $otpService->publicPolicy(),
            ], 422);
        }

        $user = WBOUser::where(
            'user_id',
            (int) $pendingUser['id']
        )->first();

        if (!$user) {
            $this->clearSignupOtp($request);

            return response()->json([
                'success' => false,
                'message' => 'The account could not be found.',
                'redirect' => '/signup',
            ], 404);
        }

        if ($user->account_status === 'disabled') {
            $this->clearSignupOtp($request);

            return response()->json([
                'success' => false,
                'message' => 'This account has been disabled.',
                'redirect' => '/login',
            ], 403);
        }

        if ($user->account_status === 'active') {
            $this->clearSignupOtp($request);

            return response()->json([
                'success' => true,
                'message' =>
                    'Your account is already verified. You may log in.',
                'redirect' => '/login',
            ]);
        }

        if ($user->account_status !== 'pending_verification') {
            $this->clearSignupOtp($request);

            return response()->json([
                'success' => false,
                'message' => 'This account cannot be verified.',
                'redirect' => '/login',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $user->account_status = 'active';
            $user->email_verified_at = now();
            $user->save();

            try {
                DB::table('WBO_AuditLogs')->insert([
                    'user_id' => (int) $user->user_id,
                    'action' => 'ACCOUNT_VERIFIED',
                    'description' =>
                        'User successfully verified their email and activated their account',
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }

            DB::commit();

            // Rotate the anonymous verification session ID.
            $request->session()->regenerate();

            $this->clearSignupOtp($request);

            return response()->json([
                'success' => true,
                'message' =>
                    'Your account has been verified successfully. You may now log in.',
                'redirect' => '/login',
            ]);
        } catch (\Throwable $e) {
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

    public function resend(
        Request $request,
        OtpService $otpService
    ) {
        $pendingUser =
            $request->session()->get('signup_pending_user');

        if (!$pendingUser) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Your verification session has ended. Log in with your email and password to restart verification.',
                'redirect' => '/login',
            ], 401);
        }

        $user = WBOUser::where(
            'user_id',
            (int) $pendingUser['id']
        )->first();

        if (!$user) {
            $this->clearSignupOtp($request);

            return response()->json([
                'success' => false,
                'message' => 'The account could not be found.',
                'redirect' => '/signup',
            ], 404);
        }

        if ($user->account_status === 'active') {
            $this->clearSignupOtp($request);

            return response()->json([
                'success' => true,
                'message' =>
                    'Your account is already verified. You may log in.',
                'redirect' => '/login',
            ]);
        }

        if ($user->account_status !== 'pending_verification') {
            $this->clearSignupOtp($request);

            return response()->json([
                'success' => false,
                'message' =>
                    'This account is not available for verification.',
                'redirect' => '/login',
            ], 403);
        }

        $resendCount =
            (int) $request->session()->get(
                'signup_otp_resend_count',
                0
            );

        if ($resendCount >= $otpService->maxResends()) {
            return response()->json([
                'success' => false,
                'message' =>
                    'You have reached the maximum number of OTP resends. Log in again to restart account verification.',
                'resends_remaining' => 0,
                'otp_policy' => $otpService->publicPolicy(),
            ], 429);
        }

        $lastSent =
            (int) $request->session()->get(
                'signup_otp_last_sent',
                0
            );

        $secondsPassed =
            now()->timestamp - $lastSent;

        $cooldown =
            $otpService->resendCooldownSeconds();

        if (
            $lastSent > 0 &&
            $secondsPassed < $cooldown
        ) {
            $secondsRemaining =
                $cooldown - $secondsPassed;

            return response()->json([
                'success' => false,
                'message' =>
                    "Please wait {$secondsRemaining} seconds before requesting another OTP.",
                'seconds_remaining' => $secondsRemaining,
                'otp_policy' => $otpService->publicPolicy(),
            ], 429);
        }

        $newOtp = $otpService->generate();

        try {
            $otpService->send(
                $user,
                $newOtp,
                'signup'
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to resend the OTP. Please try again.',
            ], 500);
        }

        // Replacing signup_otp_hash invalidates the previous OTP immediately.
        $request->session()->put([
            'signup_pending_user' => [
                'id' => (int) $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'signup_otp_hash' => $otpService->hash($newOtp),
            'signup_otp_expiry' =>
                $otpService->expiryTimestamp(),
            'signup_otp_resend_count' =>
                $resendCount + 1,
            'signup_otp_attempt_count' => 0,
            'signup_otp_last_sent' => now()->timestamp,
        ]);

        $remaining =
            $otpService->maxResends() - ($resendCount + 1);

        return response()->json([
            'success' => true,
            'message' =>
                "A new OTP has been sent. Resends remaining: {$remaining}.",
            'resends_remaining' => $remaining,
            'otp_policy' => $otpService->publicPolicy(),
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