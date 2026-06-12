<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthThrottleMessage
{
    public const BASE_MESSAGE = 'تعداد تلاش‌ها زیاد شد. لطفاً کمی بعد دوباره امتحان کنید.';

    public static function forException(TooManyRequestsHttpException $exception): string
    {
        $retryAfter = $exception->getHeaders()['Retry-After'] ?? null;

        if ($retryAfter !== null && is_numeric($retryAfter)) {
            $seconds = (int) $retryAfter;

            if ($seconds > 0) {
                return self::BASE_MESSAGE.' لطفاً '.$seconds.' ثانیه صبر کنید.';
            }
        }

        return self::BASE_MESSAGE;
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
