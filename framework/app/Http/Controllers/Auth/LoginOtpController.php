<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WBOUser;
use App\Services\AuthSessionService;
use App\Services\OtpService;
use App\Services\TrustedDeviceService;
use Illuminate\Http\Request;

class LoginOtpController extends Controller
{
    public function verify(
        Request $request,
        OtpService $otpService,
        AuthSessionService $sessions,
        TrustedDeviceService $trustedDevices
    ) {
        $length = $otpService->length();

        $request->validate([
            'otp' => "required|digits:{$length}",
        ]);

        $pendingUser =
            $request->session()->get('pending_user');

        if (!$pendingUser) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Your login verification session has ended. Please log in again.',
                'redirect' => '/login',
            ], 401);
        }

        $storedHash =
            $request->session()->get('otp_hash');

        $otpExpiry =
            $request->session()->get('otp_expiry');

        if (!$storedHash) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This OTP is no longer valid. Please request a new OTP.',
                'can_resend' => true,
                'otp_policy' =>
                    $otpService->publicPolicy(),
            ], 422);
        }

        if (
            !$otpExpiry ||
            now()->timestamp > (int) $otpExpiry
        ) {
            $request->session()->forget([
                'otp_hash',
                'otp_expiry',
                'otp_attempt_count',
            ]);

            return response()->json([
                'success' => false,
                'message' =>
                    'Your OTP has expired. Please request a new OTP.',
                'can_resend' => true,
                'otp_policy' =>
                    $otpService->publicPolicy(),
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
                    'otp_attempt_count',
                    0
                ) + 1;

            $request->session()->put(
                'otp_attempt_count',
                $attempts
            );

            $remaining = max(
                0,
                $otpService->maxAttempts() - $attempts
            );

            if ($remaining === 0) {
                $request->session()->forget([
                    'otp_hash',
                    'otp_expiry',
                ]);

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Too many incorrect attempts. Request a new OTP.',
                    'attempts_remaining' => 0,
                    'can_resend' => true,
                    'otp_policy' =>
                        $otpService->publicPolicy(),
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' =>
                    "Invalid OTP code. Attempts remaining: {$remaining}.",
                'attempts_remaining' => $remaining,
                'otp_policy' =>
                    $otpService->publicPolicy(),
            ], 422);
        }

        $user = WBOUser::where(
            'user_id',
            (int) $pendingUser['id']
        )->first();

        if (!$user) {
            $this->clearLoginOtp($request);

            return response()->json([
                'success' => false,
                'message' =>
                    'The account could not be found.',
                'redirect' => '/login',
            ], 404);
        }

        if ($user->account_status !== 'active') {
            $this->clearLoginOtp($request);

            return response()->json([
                'success' => false,
                'message' =>
                    $user->account_status === 'disabled'
                        ? 'Your account has been disabled.'
                        : 'Your account is not available for login.',
                'redirect' => '/login',
            ], 403);
        }

        $rememberDevice =
            (bool) $request->session()->get(
                'otp_remember_device',
                false
            );

        $sessions->start(
            $request,
            $user,
            'LOGIN',
            'User successfully logged in after OTP verification.'
        );

        $cookie = null;

        if ($rememberDevice) {
            try {
                $cookie =
                    $trustedDevices->issueCookie(
                        $request,
                        $user
                    );
            } catch (\Throwable $e) {
                // Login remains successful even if remembering the
                // device fails. The user will simply need OTP next time.
                report($e);
            }
        }

        $this->clearLoginOtp($request);

        $response = response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'redirect' =>
                $this->dashboardForRole($user->role),
            'trusted_device_created' =>
                $cookie !== null,
        ]);

        if ($cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }

    public function resend(
        Request $request,
        OtpService $otpService
    ) {
        $pendingUser =
            $request->session()->get('pending_user');

        if (!$pendingUser) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Your login verification session has ended. Please log in again.',
                'redirect' => '/login',
            ], 401);
        }

        $user = WBOUser::where(
            'user_id',
            (int) $pendingUser['id']
        )->first();

        if (
            !$user ||
            $user->account_status !== 'active'
        ) {
            $this->clearLoginOtp($request);

            return response()->json([
                'success' => false,
                'message' =>
                    'This account is no longer available for login.',
                'redirect' => '/login',
            ], 403);
        }

        $resendCount =
            (int) $request->session()->get(
                'otp_resend_count',
                0
            );

        if (
            $resendCount >=
            $otpService->maxResends()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'You have reached the maximum number of OTP resends. You can restart login to receive a fresh OTP.',
                'resends_remaining' => 0,
                'otp_policy' =>
                    $otpService->publicPolicy(),
            ], 429);
        }

        $lastSent =
            (int) $request->session()->get(
                'otp_last_sent',
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
                'seconds_remaining' =>
                    $secondsRemaining,
                'otp_policy' =>
                    $otpService->publicPolicy(),
            ], 429);
        }

        $newOtp = $otpService->generate();

        try {
            $otpService->send(
                $user,
                $newOtp,
                'login'
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to resend OTP. Please try again.',
            ], 500);
        }

        $request->session()->put([
            'pending_user' => [
                'id' => (int) $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'otp_hash' =>
                $otpService->hash($newOtp),
            'otp_expiry' =>
                $otpService->expiryTimestamp(),
            'otp_resend_count' =>
                $resendCount + 1,
            'otp_attempt_count' => 0,
            'otp_last_sent' => now()->timestamp,
        ]);

        $remaining =
            $otpService->maxResends() -
            ($resendCount + 1);

        return response()->json([
            'success' => true,
            'message' =>
                "A new OTP has been sent. Resends remaining: {$remaining}.",
            'resends_remaining' => $remaining,
            'otp_policy' =>
                $otpService->publicPolicy(),
        ]);
    }

    private function clearLoginOtp(
        Request $request
    ): void {
        $request->session()->forget([
            'pending_user',
            'otp_code',
            'otp_hash',
            'otp_expiry',
            'otp_resend_count',
            'otp_attempt_count',
            'otp_last_sent',
            'otp_remember_device',
        ]);
    }

    private function dashboardForRole(
        ?string $role
    ): string {
        return match ($role) {
            'super_admin' => '/super-admin',
            'Operations_Manager' => '/operations-manager',
            'Purchasing_Manager' => '/purchasing-manager',
            'Warehouse_Admin' => '/warehouse-admin',
            'Sales_Manager' => '/sales-manager',
            'Purchasing_Staff' => '/purchasing-staff',
            'Inventory_Controller' => '/inventory-controller',
            'Sales_Staff' => '/sales-staff',
            'User_Admin' => '/user-admin',
            default => '/user',
        };
    }
}