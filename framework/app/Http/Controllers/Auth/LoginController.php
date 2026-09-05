<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WBOUser;
use App\Services\Auth\AuthSessionService;
use App\Services\Auth\OtpService;
use App\Services\Auth\TrustedDeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(
        Request $request,
        OtpService $otpService,
        TrustedDeviceService $trustedDevices,
        AuthSessionService $sessions
    ) {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember_device' => 'sometimes|boolean',
        ]);

        $email = strtolower(trim($validated['email']));

        $user = WBOUser::where(
            'email',
            $email
        )->first();

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

        $this->clearAuthenticatedUser($request);
        $this->clearLoginOtp($request);

        /*
        |--------------------------------------------------------------------------
        | Resume unfinished signup verification
        |--------------------------------------------------------------------------
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
                'signup_pending_user' =>
                    $this->pendingUser($user),
                'signup_otp_hash' =>
                    $otpService->hash($otp),
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
                'otp_policy' =>
                    $otpService->publicPolicy(),
            ]);
        }

        if ($user->account_status !== 'active') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Your account is currently unavailable.',
            ], 403);
        }

        $this->clearSignupOtp($request);

        /*
        |--------------------------------------------------------------------------
        | Trusted device login
        |--------------------------------------------------------------------------
        |
        | Password is always required. A valid remembered device only skips the
        | OTP step.
        |
        */

        if (
            $trustedDevices->validForUser(
                $request,
                $user
            )
        ) {
            $sessions->start(
                $request,
                $user,
                'LOGIN_TRUSTED_DEVICE',
                'User logged in with password from a trusted device.'
            );

            return response()->json([
                'success' => true,
                'authenticated' => true,
                'otp_skipped' => true,
                'message' =>
                    'Trusted device recognized. Login successful.',
                'redirect' =>
                    $this->dashboardForRole($user->role),
            ]);
        }

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
            'pending_user' =>
                $this->pendingUser($user),
            'otp_hash' =>
                $otpService->hash($otp),
            'otp_expiry' =>
                $otpService->expiryTimestamp(),
            'otp_resend_count' => 0,
            'otp_attempt_count' => 0,
            'otp_last_sent' => now()->timestamp,
            'otp_remember_device' =>
                (bool) ($validated['remember_device'] ?? false),
        ]);

        return response()->json([
            'success' => true,
            'authenticated' => false,
            'message' =>
                'OTP has been sent to your email.',
            'redirect' => '/login-otp',
            'verification' => 'login',
            'email' => $user->email,
            'otp_policy' =>
                $otpService->publicPolicy(),
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

    private function clearAuthenticatedUser(
        Request $request
    ): void {
        $request->session()->forget([
            'logged_in',
            'user_id',
            'name',
            'email',
            'role',
            'last_activity',
            'auth_session_id',
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

    private function clearSignupOtp(
        Request $request
    ): void {
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