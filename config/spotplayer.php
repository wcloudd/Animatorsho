<?php

return [

    'enabled' => filter_var(env('SPOTPLAYER_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'api_base_url' => rtrim((string) env('SPOTPLAYER_API_BASE_URL', 'https://panel.spotplayer.ir'), '/'),

    'api_key' => env('SPOTPLAYER_API_KEY'),

    'timeout' => (int) env('SPOTPLAYER_TIMEOUT', 15),

    'test_mode' => filter_var(env('SPOTPLAYER_TEST_MODE', false), FILTER_VALIDATE_BOOLEAN),

    'default_device' => [
        'p0' => 2,
        'p1' => 0,
        'p2' => 0,
        'p3' => 0,
        'p4' => 0,
        'p5' => 0,
        'p6' => 2,
    ],

];
