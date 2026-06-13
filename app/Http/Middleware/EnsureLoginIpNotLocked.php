<?php

namespace App\Http\Middleware;

use App\Services\Security\LoginIpAbuseTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class EnsureLoginIpNotLocked
{
    public function __construct(
        private readonly LoginIpAbuseTracker $loginIpAbuse,
    ) {}

    /**
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->loginIpAbuse->isLockedOut($request)) {
            return $next($request);
        }

        throw new TooManyRequestsHttpException($this->loginIpAbuse->availableIn($request));
    }
}
