<?php

namespace App\Fortify;

use App\Services\Security\LoginIpAbuseTracker;
use App\Support\LoginIdentifier;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Http\Request;
use Laravel\Fortify\LoginRateLimiter as FortifyLoginRateLimiter;

class LoginRateLimiter extends FortifyLoginRateLimiter
{
    public function __construct(
        CacheRateLimiter $limiter,
        private readonly LoginIpAbuseTracker $loginIpAbuse,
    ) {
        parent::__construct($limiter);
    }

    public function tooManyAttempts(Request $request): bool
    {
        return $this->limiter->tooManyAttempts(
            $this->throttleKey($request),
            $this->maxAttempts(),
        );
    }

    public function increment(Request $request): void
    {
        $this->limiter->hit($this->throttleKey($request), $this->decaySeconds());

        if ($this->tooManyAttempts($request)) {
            $this->loginIpAbuse->recordBatchLockout($request);
        }
    }

    public function availableIn(Request $request): int
    {
        return $this->limiter->availableIn($this->throttleKey($request));
    }

    public function clear(Request $request): void
    {
        $this->limiter->clear($this->throttleKey($request));
    }

    protected function throttleKey(Request $request): string
    {
        return LoginIdentifier::throttleKey($request);
    }

    private function maxAttempts(): int
    {
        return (int) config('security.rate_limits.login.max_attempts', 5);
    }

    private function decaySeconds(): int
    {
        return (int) config('security.rate_limits.login.decay_minutes', 20) * 60;
    }
}
