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
            'decay_minutes' => 20,
        ],
        'auth-identifier' => [
            'max_attempts' => 5,
            'decay_minutes' => 20,
        ],
        'mobile-otp-send' => [
            'max_attempts' => 3,
            'decay_minutes' => 1,
        ],
        'mobile-otp-verify' => [
            'max_attempts' => 5,
            'decay_minutes' => 20,
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
        'exercise-submission-create' => [
            'max_attempts' => 5,
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

    /*
    |--------------------------------------------------------------------------
    | Login IP Abuse Protection
    |--------------------------------------------------------------------------
    |
    | Progressive temporary IP lockout when repeated login lockout batches occur
    | from the same IP within the batch window. Uses cache rate limiting only.
    |
    */

    'login_ip_abuse' => [
        'batch_window_minutes' => 60,
        'batches_before_ip_lockout' => 2,
        'ip_lockout_minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Honeypot
    |--------------------------------------------------------------------------
    |
    | Offline bot trap for consultation and support POST forms. The hidden
    | field must stay empty; any value triggers a generic rejection response.
    |
    */

    'honeypot' => [
        'enabled' => env('SECURITY_HONEYPOT_ENABLED', true),
        'field_name' => 'preferred_contact_window',
        'message' => 'در ارسال فرم مشکلی پیش آمد. لطفاً دوباره تلاش کنید.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Event Logging
    |--------------------------------------------------------------------------
    |
    | Structured warning logs for suspicious security events. Writes to the
    | dedicated security log channel configured in config/logging.php.
    |
    */

    'logging' => [
        'enabled' => env('SECURITY_LOGGING_ENABLED', true),
        'channel' => env('SECURITY_LOG_CHANNEL', 'security'),
        'user_agent_max_length' => 200,
        'database_enabled' => env('SECURITY_LOGGING_DATABASE_ENABLED', true),
        'database_retention_days' => (int) env('SECURITY_LOGGING_RETENTION_DAYS', 90),
    ],

];
