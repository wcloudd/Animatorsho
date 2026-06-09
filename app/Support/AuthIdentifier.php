<?php

namespace App\Support;

class AuthIdentifier
{
    public const UNKNOWN_EMAIL_MESSAGE = 'برای ساخت حساب جدید، لطفاً با شماره موبایل ثبت‌نام کنید.';

    public static function parse(?string $raw): ?ParsedAuthIdentifier
    {
        if ($raw === null) {
            return null;
        }

        $value = trim($raw);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, '@')) {
            $email = strtolower($value);

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            return new ParsedAuthIdentifier(ParsedAuthIdentifier::Email, $email);
        }

        $mobile = IranianMobile::normalize($value);

        if ($mobile !== null) {
            return new ParsedAuthIdentifier(ParsedAuthIdentifier::Mobile, $mobile);
        }

        return null;
    }

    public static function validationMessage(?string $raw): string
    {
        $value = trim((string) $raw);

        if ($value === '') {
            return 'موبایل یا ایمیل خود را وارد کنید.';
        }

        if (str_contains($value, '@')) {
            return 'ایمیل معتبر وارد کنید.';
        }

        if (preg_match('/\d/', $value)) {
            return IranianMobile::validationMessage($value);
        }

        return 'موبایل یا ایمیل معتبر وارد کنید.';
    }
}
