<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Down payment percentage
    |--------------------------------------------------------------------------
    |
    | Percentage of the installment total price the user must pay up front
    | before the installment request becomes reviewable by an admin.
    |
    */

    'down_payment_percent' => 40,

    /*
    |--------------------------------------------------------------------------
    | Installment terms
    |--------------------------------------------------------------------------
    |
    | Each term adds a fixed surcharge (in toman) on top of the cash price to
    | produce the installment total price. These rules are versioned here, but
    | every created order/payment snapshots the calculated amounts so existing
    | orders are never recalculated when this config changes.
    |
    */

    'terms' => [
        'one_month' => [
            'label' => '۱ ماهه',
            'months' => 1,
            'extra_toman' => 500_000,
        ],
        'two_months' => [
            'label' => '۲ ماهه',
            'months' => 2,
            'extra_toman' => 1_000_000,
        ],
    ],

];
