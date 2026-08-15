<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\WBOUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        // ==========================================
        // VALIDATE SIGNUP FORM
        // ==========================================

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


        // ==========================================
        // START DATABASE TRANSACTION
        // ==========================================

        DB::beginTransaction();


        try {

            // ==========================================
            // CREATE USER
            // ==========================================

            $user = WBOUser::create([

                'name' =>
                    $validated['name'],

                'email' =>
                    $validated['email'],

                'contact_number' =>
                    $validated['contact_number'],

                'password_hash' =>
                    Hash::make(
                        $validated['password']
                    ),

                // Public signup users start here
                'role' =>
                    'System_User',

                'account_status' =>
                    'pending_verification',

                'email_verified_at' =>
                    null,
            ]);


            // ==========================================
            // GENERATE 6-DIGIT OTP
            // ==========================================

            $otp = (string) random_int(
                100000,
                999999
            );


            // ==========================================
            // SAVE SIGNUP VERIFICATION SESSION
            // ==========================================

            session([

                'signup_pending_user' => [

                    'id' =>
                        (int) $user->user_id,

                    'name' =>
                        $user->name,

                    'email' =>
                        $user->email,

                    'role' =>
                        $user->role,
                ],


                // Current OTP
                'signup_otp_code' =>
                    $otp,


                // OTP expires after 5 minutes
                'signup_otp_expiry' =>
                    now()->addMinutes(5)->timestamp,


                // Maximum 2 resends later
                'signup_otp_resend_count' =>
                    0,


                // Used for resend cooldown
                'signup_otp_last_sent' =>
                    now()->timestamp,
            ]);


            // ==========================================
            // SEND OTP THROUGH GMAIL
            // ==========================================

            Mail::to(
                $user->email
            )->send(

                new OtpMail(
                    $otp,
                    $user->name
                )

            );


            // ==========================================
            // SAVE USER
            // ==========================================

            DB::commit();


            // ==========================================
            // SUCCESS RESPONSE FOR REACT
            // ==========================================

            return response()->json([

                'success' =>
                    true,

                'message' =>
                    'Account created. Verification OTP sent.',

                'redirect' =>
                    '/signup-verify',

            ], 201);

        }

        catch (\Throwable $e) {

            // ==========================================
            // CANCEL DATABASE INSERT
            // ==========================================

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }


            // ==========================================
            // REMOVE TEMPORARY SIGNUP SESSION
            // ==========================================

            session()->forget([

                'signup_pending_user',

                'signup_otp_code',

                'signup_otp_expiry',

                'signup_otp_resend_count',

                'signup_otp_last_sent',

            ]);


            // Put technical error in Laravel log
            report($e);


            // ==========================================
            // ERROR RESPONSE FOR REACT
            // ==========================================

            return response()->json([

                'success' =>
                    false,

                'message' =>
                    'Unable to create your account. Please try again.',

            ], 500);
        }
    }
}