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
        'password-reset-email-send' => [
            'max_attempts' => 3,
            'decay_minutes' => 1,
        ],
        'password-reset-email-submit' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
        'password-reset-mobile-submit' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
        'support-ticket-create' => [
            'max_attempts' => 3,
            'decay_minutes' => 1,
        ],
        'support-ticket-reply' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],
        'consultation-submit' => [
            'max_attempts' => 3,
            'decay_minutes' => 1,
        ],
        'checkout-order' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
        'payment-retry' => [
            'max_attempts' => 3,
            'decay_minutes' => 1,
        ],
        'payment-cancel' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Online Payment Recovery
    |--------------------------------------------------------------------------
    */

    'online_payment' => [
        'max_retries_per_order' => 5,
    ],

    'support' => [
        'max_open_tickets' => 3,
    ],

];
