<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Idle session policy
    |--------------------------------------------------------------------------
    |
    | The current testing policy is one minute total inactivity with a
    | warning during the final 15 seconds.
    |
    */

    'idle_seconds' => max(
        30,
        (int) env('SESSION_IDLE_SECONDS', 60)
    ),

    'warning_seconds' => max(
        5,
        (int) env('SESSION_IDLE_WARNING_SECONDS', 15)
    ),

    /*
    |--------------------------------------------------------------------------
    | Trusted device policy
    |--------------------------------------------------------------------------
    */

    'trusted_device' => [
        'days' => max(
            1,
            (int) env('TRUSTED_DEVICE_DAYS', 30)
        ),

        'cookie_name' => env(
            'TRUSTED_DEVICE_COOKIE',
            'wbo_trusted_device'
        ),

        // Leave null to automatically follow the current HTTPS request.
        'secure_cookie' => env(
            'TRUSTED_DEVICE_SECURE_COOKIE'
        ),

        'same_site' => env(
            'TRUSTED_DEVICE_SAME_SITE',
            'lax'
        ),
    ],
];