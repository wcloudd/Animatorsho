<?php

namespace App\Support;

class IranianMobile
{
    /**
     * Normalize an Iranian mobile number to canonical 09XXXXXXXXX format.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        if (! preg_match('/^09\d{9}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    public static function isValid(?string $value): bool
    {
        return self::normalize($value) !== null;
    }

    public static function mask(?string $mobile): ?string
    {
        if ($mobile === null || $mobile === '') {
            return null;
        }

        if (strlen($mobile) !== 11) {
            return $mobile;
        }

        return substr($mobile, 0, 4).'***'.substr($mobile, -4);
    }
}
