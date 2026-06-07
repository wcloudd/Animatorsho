<?php

return [

    'merchant_id' => env('ZARINPAL_MERCHANT_ID'),

    'sandbox' => filter_var(env('ZARINPAL_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),

];
