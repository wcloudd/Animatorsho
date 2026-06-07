<?php

return [

    'card_number' => env('CARD_TO_CARD_NUMBER'),

    'card_owner_name' => env('CARD_TO_CARD_OWNER_NAME'),

    'receipt_max_kb' => (int) env('CARD_TO_CARD_RECEIPT_MAX_KB', 5120),

    'receipt_mimes' => ['jpg', 'jpeg', 'png', 'webp'],

];
