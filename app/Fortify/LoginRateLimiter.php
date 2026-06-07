<?php

namespace App\Fortify;

use App\Support\LoginIdentifier;
use Illuminate\Http\Request;
use Laravel\Fortify\LoginRateLimiter as FortifyLoginRateLimiter;

class LoginRateLimiter extends FortifyLoginRateLimiter
{
    /**
     * Get the throttle key for the given request.
     */
    protected function throttleKey(Request $request): string
    {
        return LoginIdentifier::throttleKey($request);
    }
}
