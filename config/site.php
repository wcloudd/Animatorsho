<?php

return [

    'purchases_enabled' => filter_var(env('PURCHASES_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'maintenance' => [
        'title' => env('MAINTENANCE_TITLE', 'در حال بروزرسانی هستیم'),
        'message' => env(
            'MAINTENANCE_MESSAGE',
            'در حال به‌روزرسانی سایت هستیم. لطفاً چند دقیقه دیگر دوباره سر بزنید.',
        ),
    ],

];
