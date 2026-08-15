<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;


class RegisterController extends Controller
{
    public function register(Request $request)
    {
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
                'unique:WBO_Users,email',
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

        DB::beginTransaction();

        try {

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'],

                'password_hash' => Hash::make(
                    $validated['password']
                ),

                'role' => 'System_User',

                'account_status' =>
                    'pending_verification',

                'email_verified_at' => null,
            ]);

            $otp = (string) random_int(
                100000,
                999999
            );

            session([
                'signup_pending_user' => [
                    'id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],

                'signup_otp_code' => $otp,

                'signup_otp_expiry' =>
                    now()->addMinutes(5)->timestamp,

                'signup_otp_resend_count' => 0,

                'signup_otp_last_sent' =>
                    now()->timestamp,
            ]);

            Mail::to($user->email)->send(
                new OtpMail(
                    $otp,
                    $user->name
                )
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' =>
                    'Account created. Verification OTP sent.',
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to create your account. Please try again.',
            ], 500);
        }
    }
}
