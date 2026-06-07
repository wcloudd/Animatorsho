<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutOrderController;
use App\Http\Controllers\CheckoutZarinpalCallbackController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileMobileVerificationController;
use App\Http\Controllers\ProfileOrderController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'animatorsho/index')->name('home');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');

Route::get('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');

Route::inertia('/checkout/result', 'checkout/result')->name('checkout.result');

Route::get('/checkout/zarinpal/callback', CheckoutZarinpalCallbackController::class)
    ->name('checkout.zarinpal.callback');

Route::get('/consultation', [ConsultationController::class, 'index'])->name('consultation');
Route::post('/consultation', [ConsultationController::class, 'store'])
    ->middleware(['auth', 'verified.mobile', 'throttle:consultation-submit'])
    ->name('consultation.store');

Route::middleware('auth')->group(function () {
    Route::post('/checkout/orders', [CheckoutOrderController::class, 'store'])
        ->middleware('verified.mobile')
        ->name('checkout.orders.store');

    Route::get('profile/mobile', [ProfileMobileVerificationController::class, 'create'])
        ->name('profile.mobile.create');
    Route::post('profile/mobile/send-code', [ProfileMobileVerificationController::class, 'sendCode'])
        ->middleware('throttle:mobile-otp-send')
        ->name('profile.mobile.send-code');
    Route::post('profile/mobile/send-existing-code', [ProfileMobileVerificationController::class, 'sendExistingCode'])
        ->middleware('throttle:mobile-otp-send')
        ->name('profile.mobile.send-existing-code');
    Route::post('profile/mobile/resend-code', [ProfileMobileVerificationController::class, 'resendCode'])
        ->middleware('throttle:mobile-otp-send')
        ->name('profile.mobile.resend-code');
    Route::get('profile/mobile/verify', [ProfileMobileVerificationController::class, 'verifyForm'])
        ->name('profile.mobile.verify');
    Route::post('profile/mobile/verify', [ProfileMobileVerificationController::class, 'verify'])
        ->middleware('throttle:mobile-otp-verify')
        ->name('profile.mobile.verify.store');

    Route::get('support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::post('support/tickets', [SupportTicketController::class, 'store'])
        ->middleware(['throttle:support-ticket', 'verified.mobile'])
        ->name('support.tickets.store');
    Route::get('support/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support.tickets.show');
    Route::post('support/tickets/{ticket}/messages', [SupportTicketController::class, 'storeMessage'])
        ->middleware(['throttle:support-ticket', 'verified.mobile'])
        ->name('support.tickets.messages.store');
    Route::get('support/tickets/{ticket}/attachments/{attachment}', [SupportTicketController::class, 'downloadAttachment'])->name('support.tickets.attachments.download');
    Route::get('profile', [ProfileController::class, 'index'])->name('profile');
    Route::get('profile/settings', [ProfileController::class, 'settings'])->name('profile.settings');
    Route::post('profile/orders/{order}/retry-online-payment', [ProfileOrderController::class, 'retryOnlinePayment'])
        ->name('profile.orders.retry-online-payment');
    Route::post('profile/orders/{order}/cancel', [ProfileOrderController::class, 'cancel'])
        ->name('profile.orders.cancel');
});

Route::redirect('dashboard', '/')->name('dashboard');

require __DIR__.'/fortify.php';
require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
