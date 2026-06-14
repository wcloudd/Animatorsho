<?php

namespace App\Providers;

use App\Support\AuthIdentifier;
use App\Support\IranianMobile;
use App\Support\LoginIdentifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap security-related application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure named rate limiters used across auth, consultation, and support.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return $this->configuredLimit('login')->by(LoginIdentifier::throttleKey($request));
        });

        RateLimiter::for('auth-identifier', function (Request $request) {
            $raw = trim((string) $request->input('identifier', 'unknown'));
            $parsed = AuthIdentifier::parse($raw);
            $key = $parsed !== null ? $parsed->value : Str::lower($raw);

            return $this->configuredLimit('auth-identifier')->by($key.'|'.$request->ip());
        });

        RateLimiter::for('mobile-otp-send', function (Request $request) {
            $mobile = IranianMobile::normalize($request->input('mobile')) ?? 'unknown';

            return $this->configuredLimit('mobile-otp-send')->by($mobile.'|'.$request->ip());
        });

        RateLimiter::for('mobile-otp-verify', function (Request $request) {
            $mobile = $request->session()->get('mobile_otp.mobile', 'unknown');

            return $this->configuredLimit('mobile-otp-verify')->by($mobile.'|'.$request->ip());
        });

        RateLimiter::for('registration-otp-send', function (Request $request) {
            $mobile = $request->session()->get('registration_otp.mobile')
                ?? IranianMobile::normalize($request->input('mobile'))
                ?? 'unknown';

            return $this->configuredLimit('registration-otp-send')->by($mobile.'|'.$request->ip());
        });

        RateLimiter::for('registration-otp-verify', function (Request $request) {
            $mobile = $request->session()->get('registration_otp.mobile', 'unknown');

            return $this->configuredLimit('registration-otp-verify')->by($mobile.'|'.$request->ip());
        });

        RateLimiter::for('password-reset-otp-send', function (Request $request) {
            $mobile = $request->session()->get('password_reset_otp.mobile')
                ?? IranianMobile::normalize($request->input('mobile'))
                ?? 'unknown';

            return $this->configuredLimit('password-reset-otp-send')->by($mobile.'|'.$request->ip());
        });

        RateLimiter::for('password-reset-otp-verify', function (Request $request) {
            $mobile = $request->session()->get('password_reset_otp.mobile', 'unknown');

            return $this->configuredLimit('password-reset-otp-verify')->by($mobile.'|'.$request->ip());
        });

        RateLimiter::for('password-reset-email-send', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email', 'unknown')));

            return $this->configuredLimit('password-reset-email-send')->by($email.'|'.$request->ip());
        });

        RateLimiter::for('password-reset-email-submit', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email', 'unknown')));

            return $this->configuredLimit('password-reset-email-submit')->by($email.'|'.$request->ip());
        });

        RateLimiter::for('password-reset-mobile-submit', function (Request $request) {
            $userId = $request->session()->get('password_reset.user_id', 'unknown');

            return $this->configuredLimit('password-reset-mobile-submit')->by('password-reset-mobile|'.$userId.'|'.$request->ip());
        });

        RateLimiter::for('support-ticket-create', function (Request $request) {
            $userId = $request->user()?->id ?? $request->ip();

            return $this->configuredLimit('support-ticket-create')->by('support-ticket-create|'.$userId);
        });

        RateLimiter::for('support-ticket-reply', function (Request $request) {
            $userId = $request->user()?->id ?? $request->ip();

            return $this->configuredLimit('support-ticket-reply')->by('support-ticket-reply|'.$userId);
        });

        RateLimiter::for('consultation-submit', function (Request $request) {
            $userId = $request->user()?->id ?? $request->ip();

            return $this->configuredLimit('consultation-submit')->by('consultation-submit|'.$userId);
        });

        RateLimiter::for('exercise-submission-create', function (Request $request) {
            $userId = $request->user()?->id ?? $request->ip();

            return $this->configuredLimit('exercise-submission-create')->by('exercise-submission-create|'.$userId);
        });

        RateLimiter::for('checkout-order', function (Request $request) {
            return $this->configuredLimit('checkout-order')
                ->by($this->authenticatedUserThrottleKey($request, 'checkout-order'));
        });

        RateLimiter::for('payment-retry', function (Request $request) {
            return $this->configuredLimit('payment-retry')
                ->by($this->authenticatedUserThrottleKey($request, 'payment-retry'));
        });

        RateLimiter::for('payment-cancel', function (Request $request) {
            return $this->configuredLimit('payment-cancel')
                ->by($this->authenticatedUserThrottleKey($request, 'payment-cancel'));
        });
    }

    private function authenticatedUserThrottleKey(Request $request, string $prefix): string
    {
        $userId = $request->user()?->id ?? 'guest';

        return $prefix.'|'.$userId.'|'.$request->ip();
    }

    private function configuredLimit(string $name): Limit
    {
        $maxAttempts = (int) config("security.rate_limits.{$name}.max_attempts");
        $decayMinutes = (int) config("security.rate_limits.{$name}.decay_minutes");

        return Limit::perMinutes($decayMinutes, $maxAttempts);
    }
}
