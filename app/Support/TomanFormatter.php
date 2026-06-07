<?php

namespace App\Support;

class TomanFormatter
{
    private const PERSIAN_DIGITS = [
        '0' => '۰',
        '1' => '۱',
        '2' => '۲',
        '3' => '۳',
        '4' => '۴',
        '5' => '۵',
        '6' => '۶',
        '7' => '۷',
        '8' => '۸',
        '9' => '۹',
    ];

    public static function format(int $amount): string
    {
        $western = number_format($amount, 0, '.', '.');
        $persian = strtr($western, self::PERSIAN_DIGITS);

        return $persian.' تومان';
    }
}
