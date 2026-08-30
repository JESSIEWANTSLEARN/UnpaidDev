<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Policy
    |--------------------------------------------------------------------------
    |
    | These values are environment-driven so OTP behavior is not scattered as
    | hard-coded numbers throughout controllers and views.
    |
    */

    'length' => (int) env('OTP_LENGTH', 6),

    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 5),

    'resend_cooldown_seconds' => (int) env(
        'OTP_RESEND_COOLDOWN_SECONDS',
        30
    ),

    'max_resends' => (int) env('OTP_MAX_RESENDS', 2),

    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    'password_reset_completion_minutes' => (int) env(
        'PASSWORD_RESET_COMPLETION_MINUTES',
        10
    ),

    'subjects' => [
        'login' => env(
            'OTP_LOGIN_SUBJECT',
            'WalangBrownout - Login Verification'
        ),

        'signup' => env(
            'OTP_SIGNUP_SUBJECT',
            'WalangBrownout - Account Verification'
        ),

        'password_reset' => env(
            'OTP_PASSWORD_RESET_SUBJECT',
            'WalangBrownout - Password Reset Code'
        ),
    ],
];