<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthThrottleMessage
{
    public const BASE_MESSAGE = 'تلاش‌های ورود بیش از حد مجاز بود.';

    public static function forException(TooManyRequestsHttpException $exception): string
    {
        $retryAfter = $exception->getHeaders()['Retry-After'] ?? null;

        if ($retryAfter !== null && is_numeric($retryAfter)) {
            $seconds = (int) $retryAfter;

            if ($seconds > 0) {
                $minutes = max(1, (int) ceil($seconds / 60));

                return self::BASE_MESSAGE.' لطفاً حدود '.$minutes.' دقیقه دیگر دوباره تلاش کنید.';
            }
        }

        return self::BASE_MESSAGE.' لطفاً حدود ۲۰ دقیقه دیگر دوباره تلاش کنید.';
    }

    public static function appliesTo(Request $request): bool
    {
        if (! $request->header('X-Inertia')) {
            return false;
        }

        return $request->routeIs(
            'login.*',
            'register.*',
            'password.*',
            'auth.mobile.*',
        );
    }
}
