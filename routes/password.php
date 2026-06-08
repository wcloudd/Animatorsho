<?php

use App\Http\Controllers\Auth\PasswordResetMobileController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(config('fortify.middleware', ['web']))->group(function () {
    Route::middleware('guest')->group(function () {
        if (Features::enabled(Features::resetPasswords())) {
            Route::prefix('password/mobile')->name('password.mobile.')->group(function () {
                Route::post('/send-code', [PasswordResetMobileController::class, 'sendCode'])
                    ->middleware('throttle:password-reset-otp-send')
                    ->name('send-code');

                Route::post('/resend-code', [PasswordResetMobileController::class, 'resendCode'])
                    ->middleware('throttle:password-reset-otp-send')
                    ->name('resend-code');

                Route::get('/verify', [PasswordResetMobileController::class, 'verifyForm'])
                    ->name('verify');

                Route::post('/verify', [PasswordResetMobileController::class, 'verify'])
                    ->middleware('throttle:password-reset-otp-verify')
                    ->name('verify.store');

                Route::get('/reset', [PasswordResetMobileController::class, 'resetForm'])
                    ->name('reset');

                Route::post('/reset', [PasswordResetMobileController::class, 'reset'])
                    ->name('reset.store');
            });
        }
    });
});
