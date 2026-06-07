<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Support\AuthRedirect;
use App\Support\IranianMobile;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function (Request $request) {
            AuthRedirect::rememberIntendedFromQuery($request);

            return Inertia::render('auth/login', [
                'canResetPassword' => Features::enabled(Features::resetPasswords()),
                'status' => $request->session()->get('status'),
            ]);
        });

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('mobile-otp-send', function (Request $request) {
            $mobile = IranianMobile::normalize($request->input('mobile')) ?? 'unknown';

            return Limit::perMinute(3)->by($mobile.'|'.$request->ip());
        });

        RateLimiter::for('mobile-otp-verify', function (Request $request) {
            $mobile = $request->session()->get('mobile_otp.mobile', 'unknown');

            return Limit::perMinute(10)->by($mobile.'|'.$request->ip());
        });

        RateLimiter::for('registration-otp-send', function (Request $request) {
            $mobile = $request->session()->get('registration_otp.mobile')
                ?? IranianMobile::normalize($request->input('mobile'))
                ?? 'unknown';

            return Limit::perMinute(3)->by($mobile.'|'.$request->ip());
        });

        RateLimiter::for('registration-otp-verify', function (Request $request) {
            $mobile = $request->session()->get('registration_otp.mobile', 'unknown');

            return Limit::perMinute(10)->by($mobile.'|'.$request->ip());
        });

        RateLimiter::for('support-ticket', function (Request $request) {
            $userId = $request->user()?->id ?? $request->ip();

            return Limit::perMinute(5)->by('support-ticket|'.$userId);
        });

        RateLimiter::for('consultation-submit', function (Request $request) {
            $mobile = IranianMobile::normalize($request->input('mobile')) ?? 'unknown';

            return Limit::perMinute(3)->by('consultation-submit|'.$mobile.'|'.$request->ip());
        });
    }
}
