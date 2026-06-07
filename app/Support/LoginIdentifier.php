<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoginIdentifier
{
    public static function usesEmail(Request $request): bool
    {
        return $request->routeIs('login.email.store');
    }

    public static function credentialField(Request $request): string
    {
        return self::usesEmail($request) ? 'email' : 'mobile';
    }

    public static function resolve(Request $request): string
    {
        if (self::usesEmail($request)) {
            return Str::lower((string) $request->input('email', ''));
        }

        return IranianMobile::normalize($request->input('mobile'))
            ?? (string) $request->input('mobile', '');
    }

    public static function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower(self::resolve($request)).'|'.$request->ip());
    }
}
