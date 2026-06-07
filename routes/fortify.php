<?php

use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;

Route::middleware(config('fortify.middleware', ['web']))->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])
            ->name('login');

        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
            ->middleware(array_filter([
                'throttle:'.config('fortify.limiters.login', 'login'),
            ]))
            ->name('login.store');

        if (Features::enabled(Features::registration())) {
            Route::get('/register', [RegisterController::class, 'create'])
                ->name('register');

            Route::post('/register', [RegisterController::class, 'store'])
                ->middleware('throttle:registration-otp-send')
                ->name('register.store');

            Route::get('/register/verify', [RegisterController::class, 'verifyForm'])
                ->name('register.verify');

            Route::post('/register/verify', [RegisterController::class, 'verify'])
                ->middleware('throttle:registration-otp-verify')
                ->name('register.verify.store');

            Route::post('/register/resend-code', [RegisterController::class, 'resendCode'])
                ->middleware('throttle:registration-otp-send')
                ->name('register.resend-code');

            Route::post('/register/change-mobile', [RegisterController::class, 'changeMobile'])
                ->middleware('throttle:registration-otp-send')
                ->name('register.change-mobile');

            Route::post('/register/cancel', [RegisterController::class, 'cancel'])
                ->name('register.cancel');
        }

        if (Features::enabled(Features::resetPasswords())) {
            Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

            Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');

            Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

            Route::post('/reset-password', [NewPasswordController::class, 'store'])
                ->name('password.update');
        }
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');
});
