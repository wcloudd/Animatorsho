<?php

use App\Http\Controllers\Auth\MobileAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->prefix('auth/mobile')->name('auth.mobile.')->group(function () {
    Route::get('/', [MobileAuthController::class, 'create'])->name('create');
    Route::post('/send-code', [MobileAuthController::class, 'sendCode'])
        ->middleware(['login.ip', 'throttle:mobile-otp-send'])
        ->name('send-code');
    Route::post('/resend-code', [MobileAuthController::class, 'resendCode'])
        ->middleware(['login.ip', 'throttle:mobile-otp-send'])
        ->name('resend-code');
    Route::get('/verify', [MobileAuthController::class, 'verifyForm'])->name('verify');
    Route::post('/verify', [MobileAuthController::class, 'verify'])
        ->middleware(['login.ip', 'throttle:mobile-otp-verify'])
        ->name('verify.store');
});
