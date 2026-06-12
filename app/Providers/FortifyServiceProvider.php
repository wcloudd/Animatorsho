<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Fortify\LoginRateLimiter;
use App\Models\User;
use App\Services\Sms\SmsSettingsService;
use App\Support\IranianMobile;
use App\Support\LoginIdentifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter as FortifyLoginRateLimiter;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();

        $this->app->singleton(FortifyLoginRateLimiter::class, LoginRateLimiter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $password = $request->input('password');

            if (! is_string($password) || $password === '') {
                return null;
            }

            if (LoginIdentifier::usesEmail($request)) {
                $user = User::query()
                    ->where('email', LoginIdentifier::resolve($request))
                    ->first();
            } else {
                $mobile = IranianMobile::normalize($request->input('mobile'));

                if ($mobile === null) {
                    return null;
                }

                $user = User::query()
                    ->where('mobile', $mobile)
                    ->first();
            }

            if ($user === null || ! $user->hasPassword()) {
                return null;
            }

            return Hash::check($password, $user->password) ? $user : null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
            'smsAvailable' => app(SmsSettingsService::class)->isOtpDeliveryAvailable(),
        ]));

    }
}
