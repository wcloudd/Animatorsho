<?php

namespace App\Support;

use Illuminate\Http\Request;

class AuthRedirect
{
    public static function rememberIntendedFromQuery(Request $request): void
    {
        $redirect = $request->query('redirect');

        if (! self::isValidRelativePath($redirect)) {
            return;
        }

        session(['url.intended' => $redirect]);
    }

    public static function isValidRelativePath(mixed $redirect): bool
    {
        if (! is_string($redirect) || $redirect === '') {
            return false;
        }

        if (! str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return false;
        }

        return true;
    }
}
