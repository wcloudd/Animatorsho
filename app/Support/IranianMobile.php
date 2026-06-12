<?php

namespace App\Support;

class IranianMobile
{
    public const EMPTY_MESSAGE = 'شماره موبایل را وارد کنید.';

    public const INVALID_CHARACTERS_MESSAGE = 'شماره موبایل فقط باید شامل عدد باشد.';

    public const TOO_MANY_DIGITS_MESSAGE = 'شماره موبایل نباید بیشتر از ۱۱ رقم باشد.';

    public const TOO_FEW_DIGITS_MESSAGE = 'شماره موبایل باید ۱۱ رقم باشد.';

    public const WRONG_PREFIX_MESSAGE = 'شماره موبایل باید با 09 شروع شود.';

    public const GENERIC_MESSAGE = 'شماره موبایل معتبر وارد کنید (مثال: 09123456789).';

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

    public static function looksLikeMobileAttempt(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null || $digits === '') {
            return false;
        }

        return (bool) preg_match('/^(?:98)?9|^09/', $digits);
    }

    public static function validationMessage(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return self::EMPTY_MESSAGE;
        }

        $trimmed = trim($value);

        if (preg_match('/[a-zA-Z@]/', $trimmed)) {
            return self::INVALID_CHARACTERS_MESSAGE;
        }

        $rawDigits = preg_replace('/\D+/', '', $trimmed);

        if ($rawDigits === null || $rawDigits === '') {
            return self::INVALID_CHARACTERS_MESSAGE;
        }

        if (str_starts_with($rawDigits, '09')) {
            $rawLength = strlen($rawDigits);

            if ($rawLength > 11) {
                return self::TOO_MANY_DIGITS_MESSAGE;
            }

            if ($rawLength < 11) {
                return self::TOO_FEW_DIGITS_MESSAGE;
            }
        }

        $digits = $rawDigits;

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        if (strlen($digits) === 11 && ! str_starts_with($digits, '09')) {
            return self::WRONG_PREFIX_MESSAGE;
        }

        return self::GENERIC_MESSAGE;
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
