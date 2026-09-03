<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WBOUser;
use App\Services\OtpService;
use App\Services\PasswordHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    public function requestReset(
        Request $request,
        OtpService $otpService
    ) {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:100'],
        ]);

        $email = strtolower(trim($validated['email']));
        $rateKey = 'password-reset-request:' . $request->ip() . ':' . hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many password reset requests. Please wait before trying again.',
                'seconds_remaining' => RateLimiter::availableIn($rateKey),
            ], 429);
        }

        RateLimiter::hit($rateKey, 60);

        $this->clearResetSession($request);

        $otp = $otpService->generate();
        $user = WBOUser::where('email', $email)->first();

        $eligible = $user
            && in_array(
                $user->account_status,
                ['active', 'pending_verification'],
                true
            );

        if ($eligible) {
            try {
                $otpService->send($user, $otp, 'password_reset');
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => 'Password reset is temporarily unavailable. Please try again.',
                ], 503);
            }
        }

        // Store a real reset challenge only for eligible accounts. For unknown or
        // disabled accounts, store a dummy challenge so the response does not reveal
        // whether the email exists.
        $request->session()->put([
            'password_reset_pending_user' => [
                'id' => $eligible ? (int) $user->user_id : null,
                'email' => $email,
            ],
            'password_reset_otp_hash' => $otpService->hash($otp),
            'password_reset_otp_expiry' => $otpService->expiryTimestamp(),
            'password_reset_otp_resend_count' => 0,
            'password_reset_otp_attempt_count' => 0,
            'password_reset_otp_last_sent' => now()->timestamp,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'If an eligible account exists for that email, a password reset code has been sent.',
            'email' => $email,
            'otp_policy' => $otpService->publicPolicy(),
        ]);
    }

    public function verifyOtp(
        Request $request,
        OtpService $otpService
    ) {
        $length = $otpService->length();

        $validator = Validator::make($request->all(), [
            'otp' => ["required", "digits:{$length}"],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Enter the complete reset code.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pending = $request->session()->get('password_reset_pending_user');
        $storedHash = $request->session()->get('password_reset_otp_hash');
        $expiry = (int) $request->session()->get('password_reset_otp_expiry', 0);

        if (!$pending || !$storedHash || !$expiry) {
            return response()->json([
                'success' => false,
                'message' => 'Your password reset session has ended. Start again from Forgot Password.',
                'redirect' => '/forgot-password',
            ], 401);
        }

        if (now()->timestamp > $expiry) {
            $request->session()->forget([
                'password_reset_otp_hash',
                'password_reset_otp_expiry',
                'password_reset_otp_attempt_count',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The reset code has expired. Request a new code.',
                'can_resend' => true,
                'otp_policy' => $otpService->publicPolicy(),
            ], 422);
        }

        if (!$otpService->matches((string) $request->otp, (string) $storedHash)) {
            $attempts =
                (int) $request->session()->get(
                    'password_reset_otp_attempt_count',
                    0
                ) + 1;

            $request->session()->put(
                'password_reset_otp_attempt_count',
                $attempts
            );

            $remaining = max(
                0,
                $otpService->maxAttempts() - $attempts
            );

            if ($remaining === 0) {
                $request->session()->forget([
                    'password_reset_otp_hash',
                    'password_reset_otp_expiry',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Too many incorrect attempts. Request a new reset code.',
                    'attempts_remaining' => 0,
                    'can_resend' => true,
                    'otp_policy' => $otpService->publicPolicy(),
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => "Invalid reset code. Attempts remaining: {$remaining}.",
                'attempts_remaining' => $remaining,
                'otp_policy' => $otpService->publicPolicy(),
            ], 422);
        }

        $userId = $pending['id'] ?? null;
        $user = $userId
            ? WBOUser::where('user_id', (int) $userId)->first()
            : null;

        if (
            !$user ||
            !in_array(
                $user->account_status,
                ['active', 'pending_verification'],
                true
            )
        ) {
            $this->clearResetSession($request);

            return response()->json([
                'success' => false,
                'message' => 'The reset code is invalid or the reset session has expired.',
                'redirect' => '/forgot-password',
            ], 422);
        }

        $completionMinutes = max(
            1,
            (int) config(
                'otp.password_reset_completion_minutes',
                10
            )
        );

        $request->session()->forget([
            'password_reset_otp_hash',
            'password_reset_otp_expiry',
            'password_reset_otp_attempt_count',
        ]);

        $request->session()->put([
            'password_reset_verified_user_id' => (int) $user->user_id,
            'password_reset_verified_expiry' =>
                now()->addMinutes($completionMinutes)->timestamp,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reset code verified. Create your new password.',
            'completion_minutes' => $completionMinutes,
        ]);
    }

    public function resend(
        Request $request,
        OtpService $otpService
    ) {
        $pending = $request->session()->get('password_reset_pending_user');

        if (!$pending) {
            return response()->json([
                'success' => false,
                'message' => 'Your password reset session has ended. Start again.',
                'redirect' => '/forgot-password',
            ], 401);
        }

        $resendCount =
            (int) $request->session()->get(
                'password_reset_otp_resend_count',
                0
            );

        if ($resendCount >= $otpService->maxResends()) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum reset-code resends reached. Start the reset process again.',
                'resends_remaining' => 0,
                'otp_policy' => $otpService->publicPolicy(),
            ], 429);
        }

        $lastSent =
            (int) $request->session()->get(
                'password_reset_otp_last_sent',
                0
            );

        $secondsPassed = now()->timestamp - $lastSent;
        $cooldown = $otpService->resendCooldownSeconds();

        if (
            $lastSent > 0 &&
            $secondsPassed < $cooldown
        ) {
            $secondsRemaining = $cooldown - $secondsPassed;

            return response()->json([
                'success' => false,
                'message' => "Please wait {$secondsRemaining} seconds before requesting another reset code.",
                'seconds_remaining' => $secondsRemaining,
                'otp_policy' => $otpService->publicPolicy(),
            ], 429);
        }

        $newOtp = $otpService->generate();
        $userId = $pending['id'] ?? null;
        $user = $userId
            ? WBOUser::where('user_id', (int) $userId)->first()
            : null;

        $eligible = $user
            && in_array(
                $user->account_status,
                ['active', 'pending_verification'],
                true
            );

        if ($eligible) {
            try {
                $otpService->send($user, $newOtp, 'password_reset');
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to resend the reset code right now.',
                ], 503);
            }
        }

        $request->session()->put([
            'password_reset_otp_hash' => $otpService->hash($newOtp),
            'password_reset_otp_expiry' => $otpService->expiryTimestamp(),
            'password_reset_otp_resend_count' => $resendCount + 1,
            'password_reset_otp_attempt_count' => 0,
            'password_reset_otp_last_sent' => now()->timestamp,
        ]);

        $remaining =
            $otpService->maxResends() - ($resendCount + 1);

        return response()->json([
            'success' => true,
            'message' => 'If the account is eligible, a new reset code has been sent.',
            'resends_remaining' => $remaining,
            'otp_policy' => $otpService->publicPolicy(),
        ]);
    }

    public function restart(Request $request)
    {
        $this->clearResetSession($request);

        return response()->json([
            'success' => true,
            'message' => 'Password recovery restarted.',
        ]);
    }
    public function status(Request $request)
    {
        $now = now()->timestamp;

        $verifiedUserId = (int) $request->session()->get(
            'password_reset_verified_user_id',
            0
        );

        $verifiedExpiry = (int) $request->session()->get(
            'password_reset_verified_expiry',
            0
        );

        if (
            $verifiedUserId > 0 &&
            $verifiedExpiry > $now
        ) {
            $user = WBOUser::where('user_id', $verifiedUserId)->first();

            if (
                $user &&
                in_array(
                    $user->account_status,
                    ['active', 'pending_verification'],
                    true
                )
            ) {
                return response()->json([
                    'success' => true,
                    'stage' => 'reset',
                    'email' => $user->email,
                    'expires_at' => $verifiedExpiry,
                ]);
            }
        }

        $pending = $request->session()->get(
            'password_reset_pending_user'
        );

        $otpExpiry = (int) $request->session()->get(
            'password_reset_otp_expiry',
            0
        );

        if (
            is_array($pending) &&
            !empty($pending['email']) &&
            $otpExpiry > $now
        ) {
            return response()->json([
                'success' => true,
                'stage' => 'verify',
                'email' => (string) $pending['email'],
                'expires_at' => $otpExpiry,
            ]);
        }

        $this->clearResetSession($request);

        return response()->json([
            'success' => true,
            'stage' => 'request',
            'email' => null,
        ]);
    }
    public function resetPassword(
        Request $request,
        PasswordHistoryService $passwordHistory
    )
    {
        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $userId =
            (int) $request->session()->get(
                'password_reset_verified_user_id',
                0
            );

        $verifiedExpiry =
            (int) $request->session()->get(
                'password_reset_verified_expiry',
                0
            );

        if (
            $userId <= 0 ||
            $verifiedExpiry <= 0 ||
            now()->timestamp > $verifiedExpiry
        ) {
            $this->clearResetSession($request);

            return response()->json([
                'success' => false,
                'message' => 'Your verified password reset session has expired. Start again.',
                'redirect' => '/forgot-password',
            ], 401);
        }

        $user = WBOUser::where('user_id', $userId)->first();

        if (
            !$user ||
            !in_array(
                $user->account_status,
                ['active', 'pending_verification'],
                true
            )
        ) {
            $this->clearResetSession($request);

            return response()->json([
                'success' => false,
                'message' => 'This account is not available for password reset.',
                'redirect' => '/login',
            ], 403);
        }

        $passwordHistory->assertNotReused(
            (int) $user->user_id,
            $validated['password'],
            $user->password_hash
        );

        DB::transaction(function () use (
            $user,
            $validated,
            $request,
            $passwordHistory
        ) {
            $passwordHistory->rememberCurrent(
                (int) $user->user_id,
                $user->password_hash
            );

            $user->password_hash = Hash::make($validated['password']);
            $user->last_seen_at = null;
            $user->save();

            if (Schema::hasTable('WBO_UserSessions')) {
                DB::table('WBO_UserSessions')
                    ->where('user_id', $user->user_id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'logged_out_at' => now(),
                    ]);
            }

            // Password reset invalidates all remembered/trusted devices.
            if (Schema::hasTable('WBO_TrustedDevices')) {
                DB::table('WBO_TrustedDevices')
                    ->where('user_id', $user->user_id)
                    ->delete();
            }

            // Invalidate Laravel database sessions too when user_id is available.
            if (
                Schema::hasTable('sessions') &&
                Schema::hasColumn('sessions', 'user_id')
            ) {
                DB::table('sessions')
                    ->where('user_id', $user->user_id)
                    ->delete();
            }

            try {
                DB::table('WBO_AuditLogs')->insert([
                    'user_id' => (int) $user->user_id,
                    'action' => 'PASSWORD_RESET',
                    'description' => 'User reset account password using email OTP verification',
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        });

        $wasPending =
            $user->account_status === 'pending_verification';

        $this->clearResetSession($request);

        // Rotate the anonymous reset session after the credential change.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => $wasPending
                ? 'Password updated. Log in with your new password to continue email verification.'
                : 'Password updated successfully. Please log in again.',
            'redirect' => '/login',
            'pending_verification' => $wasPending,
        ]);
    }

    private function clearResetSession(Request $request): void
    {
        $request->session()->forget([
            'password_reset_pending_user',
            'password_reset_otp_hash',
            'password_reset_otp_expiry',
            'password_reset_otp_resend_count',
            'password_reset_otp_attempt_count',
            'password_reset_otp_last_sent',
            'password_reset_verified_user_id',
            'password_reset_verified_expiry',
        ]);
    }
}
