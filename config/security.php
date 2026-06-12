<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | Named rate limiters registered in SecurityServiceProvider. Each entry
    | defines the maximum number of attempts allowed per decay window.
    | Decay is applied via Limit::perMinutes(decay_minutes, max_attempts).
    |
    */

    'rate_limits' => [
        'login' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
        'auth-identifier' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
        'mobile-otp-send' => [
            'max_attempts' => 3,
            'decay_minutes' => 1,
        ],
        'mobile-otp-verify' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],
        'registration-otp-send' => [
            'max_attempts' => 3,
            'decay_minutes' => 1,
        ],
        'registration-otp-verify' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],
        'password-reset-otp-send' => [
            'max_attempts' => 3,
            'decay_minutes' => 1,
        ],
        'password-reset-otp-verify' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],
        'support-ticket' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
        'consultation-submit' => [
            'max_attempts' => 3,
            'decay_minutes' => 1,
        ],
    ],

];
