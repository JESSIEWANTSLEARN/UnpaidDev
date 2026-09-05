<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WBOUser;
use App\Services\Auth\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function register(
        Request $request,
        OtpService $otpService
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:100',
            ],

            'contact_number' => [
                'required',
                'regex:/^[0-9+\-\s]{7,20}$/',
            ],

            'password' => [
                'required',
                'string',
                'min:6',
                'confirmed',
            ],
        ]);

        $email = strtolower(trim($validated['email']));

        $existing = WBOUser::where(
            'email',
            $email
        )->first();

        /*
        |--------------------------------------------------------------------------
        | Existing email
        |--------------------------------------------------------------------------
        |
        | Active accounts must log in normally.
        | Pending accounts may resume verification only after proving ownership
        | with the same password used when the account was originally created.
        |
        */

        if ($existing) {
            if ($existing->account_status === 'disabled') {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'This account has been disabled. Please contact support.',
                ], 403);
            }

            if ($existing->account_status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'An account with this email already exists. Please log in.',
                    'redirect' => '/login',
                ], 422);
            }

            if (
                $existing->account_status === 'pending_verification'
            ) {
                if (
                    !Hash::check(
                        $validated['password'],
                        $existing->password_hash
                    )
                ) {
                    return response()->json([
                        'success' => false,
                        'message' =>
                            'An unverified account already exists for this email. Use the original password or log in to continue verification.',
                    ], 422);
                }

                $this->clearSignupOtp($request);

                $otp = $otpService->generate();

                try {
                    $otpService->send(
                        $existing,
                        $otp,
                        'signup'
                    );
                } catch (\Throwable $e) {
                    report($e);

                    return response()->json([
                        'success' => false,
                        'message' =>
                            'Unable to send a new verification code. Please try again.',
                    ], 500);
                }

                $this->storeSignupOtp(
                    $request,
                    $existing,
                    $otp,
                    $otpService
                );

                return response()->json([
                    'success' => true,
                    'message' =>
                        'Your unverified account was found. A new verification code has been sent.',
                    'redirect' => '/signup-verify',
                    'verification' => 'signup',
                    'email' => $existing->email,
                    'recovered' => true,
                    'otp_policy' => $otpService->publicPolicy(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'This account is currently unavailable.',
            ], 403);
        }

        DB::beginTransaction();

        try {
            $user = WBOUser::create([
                'name' => trim($validated['name']),
                'email' => $email,
                'contact_number' =>
                    trim($validated['contact_number']),
                'password_hash' =>
                    Hash::make($validated['password']),
                'role' => 'System_User',
                'account_status' => 'pending_verification',
                'email_verified_at' => null,
            ]);

            $otp = $otpService->generate();

            $otpService->send(
                $user,
                $otp,
                'signup'
            );

            DB::commit();

            $this->clearSignupOtp($request);

            $this->storeSignupOtp(
                $request,
                $user,
                $otp,
                $otpService
            );

            return response()->json([
                'success' => true,
                'message' =>
                    'Account created. Verification OTP sent.',
                'redirect' => '/signup-verify',
                'verification' => 'signup',
                'email' => $user->email,
                'otp_policy' => $otpService->publicPolicy(),
            ], 201);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $this->clearSignupOtp($request);

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to create your account. Please try again.',
            ], 500);
        }
    }

    private function storeSignupOtp(
        Request $request,
        WBOUser $user,
        string $otp,
        OtpService $otpService
    ): void {
        $request->session()->put([
            'signup_pending_user' => [
                'id' => (int) $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'signup_otp_hash' => $otpService->hash($otp),
            'signup_otp_expiry' =>
                $otpService->expiryTimestamp(),
            'signup_otp_resend_count' => 0,
            'signup_otp_attempt_count' => 0,
            'signup_otp_last_sent' => now()->timestamp,
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