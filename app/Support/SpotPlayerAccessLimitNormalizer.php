<?php

namespace App\Support;

class SpotPlayerAccessLimitNormalizer
{
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $normalized = trim($input);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }

    public static function isValid(?string $input): bool
    {
        if ($input === null || trim($input) === '') {
            return true;
        }

        return (bool) preg_match('/^[0-9,\-\s]+$/', trim($input));
    }
}
