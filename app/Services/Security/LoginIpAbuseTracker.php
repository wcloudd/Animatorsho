<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginIpAbuseTracker
{
    public function isLockedOut(Request $request): bool
    {
        return RateLimiter::tooManyAttempts($this->lockoutKey($request), 1);
    }

    public function availableIn(Request $request): int
    {
        return RateLimiter::availableIn($this->lockoutKey($request));
    }

    public function recordBatchLockout(Request $request): void
    {
        $batchKey = $this->batchKey($request);
        $windowSeconds = $this->batchWindowSeconds();
        $batchThreshold = $this->batchesBeforeIpLockout();

        RateLimiter::hit($batchKey, $windowSeconds);

        if (! RateLimiter::tooManyAttempts($batchKey, $batchThreshold)) {
            return;
        }

        $lockoutKey = $this->lockoutKey($request);
        $wasAlreadyLocked = RateLimiter::tooManyAttempts($lockoutKey, 1);

        RateLimiter::hit($lockoutKey, $this->ipLockoutSeconds());

        if (! $wasAlreadyLocked) {
            app(SecurityEventLogger::class)->loginIpAbuseTriggered(
                RateLimiter::attempts($batchKey),
                $request,
            );
        }
    }

    private function batchKey(Request $request): string
    {
        return 'login-ip-batches|'.$request->ip();
    }

    private function lockoutKey(Request $request): string
    {
        return 'login-ip-lockout|'.$request->ip();
    }

    private function batchWindowSeconds(): int
    {
        return (int) config('security.login_ip_abuse.batch_window_minutes', 60) * 60;
    }

    private function batchesBeforeIpLockout(): int
    {
        return (int) config('security.login_ip_abuse.batches_before_ip_lockout', 2);
    }

    private function ipLockoutSeconds(): int
    {
        return (int) config('security.login_ip_abuse.ip_lockout_minutes', 60) * 60;
    }
}
