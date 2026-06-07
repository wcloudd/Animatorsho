<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutOrderController;
use App\Http\Controllers\CheckoutZarinpalCallbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileOrderController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'animatorsho/index')->name('home');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');

Route::get('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');

Route::inertia('/checkout/result', 'checkout/result')->name('checkout.result');

Route::get('/checkout/zarinpal/callback', CheckoutZarinpalCallbackController::class)
    ->name('checkout.zarinpal.callback');

Route::inertia('/consultation', 'consultation/index')->name('consultation');

Route::middleware('auth')->group(function () {
    Route::post('/checkout/orders', [CheckoutOrderController::class, 'store'])
        ->name('checkout.orders.store');

    Route::get('support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::post('support/tickets', [SupportTicketController::class, 'store'])
        ->middleware('throttle:support-ticket')
        ->name('support.tickets.store');
    Route::get('support/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support.tickets.show');
    Route::post('support/tickets/{ticket}/messages', [SupportTicketController::class, 'storeMessage'])
        ->middleware('throttle:support-ticket')
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

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
