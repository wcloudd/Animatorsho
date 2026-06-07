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

    public static function intendedPathFromRequest(Request $request): ?string
    {
        if ($request->isMethod('GET')) {
            return self::relativePathFromUrl($request->getRequestUri());
        }

        $referer = $request->headers->get('referer');

        return self::relativePathFromFullUrl(is_string($referer) ? $referer : null);
    }

    public static function relativePathFromFullUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! self::isValidRelativePath($path)) {
            return null;
        }

        $query = parse_url($url, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return $path;
        }

        return $path.'?'.$query;
    }

    public static function relativePathFromUrl(string $url): ?string
    {
        return self::relativePathFromFullUrl($url);
    }
}
