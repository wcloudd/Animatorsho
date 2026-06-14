<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutOrderController;
use App\Http\Controllers\CheckoutZarinpalCallbackController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\CourseExercisesController;
use App\Http\Controllers\CourseHomeController;
use App\Http\Controllers\CourseResourcesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileMobileVerificationController;
use App\Http\Controllers\ProfileOrderController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/course', [CourseHomeController::class, 'index'])
    ->middleware('auth')
    ->name('course.home');

Route::get('/course/resources', [CourseResourcesController::class, 'index'])
    ->middleware('auth')
    ->name('course.resources.index');

Route::get('/course/exercises', [CourseExercisesController::class, 'index'])
    ->middleware('auth')
    ->name('course.exercises.index');

Route::get('/course/exercises/create', [CourseExercisesController::class, 'create'])
    ->middleware('auth')
    ->name('course.exercises.create');

Route::post('/course/exercises', [CourseExercisesController::class, 'store'])
    ->middleware(['auth', 'honeypot', 'throttle:exercise-submission-create'])
    ->name('course.exercises.store');

Route::get('/course/exercises/{exerciseSubmission}/attachment', [CourseExercisesController::class, 'attachment'])
    ->middleware('auth')
    ->name('course.exercises.attachment');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');

Route::get('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');

Route::inertia('/checkout/result', 'checkout/result')->name('checkout.result');

Route::get('/checkout/zarinpal/callback', CheckoutZarinpalCallbackController::class)
    ->name('checkout.zarinpal.callback');

Route::get('/consultation', [ConsultationController::class, 'index'])->name('consultation');
Route::post('/consultation', [ConsultationController::class, 'store'])
    ->middleware(['auth', 'verified.mobile', 'honeypot', 'throttle:consultation-submit'])
    ->name('consultation.store');

Route::middleware('auth')->group(function () {
    Route::post('/checkout/orders', [CheckoutOrderController::class, 'store'])
        ->middleware(['verified.mobile', 'throttle:checkout-order'])
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
        ->middleware(['honeypot', 'throttle:support-ticket-create', 'verified.mobile'])
        ->name('support.tickets.store');
    Route::get('support/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support.tickets.show');
    Route::post('support/tickets/{ticket}/messages', [SupportTicketController::class, 'storeMessage'])
        ->middleware(['honeypot', 'throttle:support-ticket-reply', 'verified.mobile'])
        ->name('support.tickets.messages.store');
    Route::get('support/tickets/{ticket}/attachments/{attachment}', [SupportTicketController::class, 'downloadAttachment'])->name('support.tickets.attachments.download');
    Route::get('profile', [ProfileController::class, 'index'])->name('profile');
    Route::get('profile/settings', [ProfileController::class, 'settings'])->name('profile.settings');
    Route::post('profile/orders/{order}/retry-online-payment', [ProfileOrderController::class, 'retryOnlinePayment'])
        ->middleware('throttle:payment-retry')
        ->name('profile.orders.retry-online-payment');
    Route::post('profile/orders/{order}/cancel', [ProfileOrderController::class, 'cancel'])
        ->middleware('throttle:payment-cancel')
        ->name('profile.orders.cancel');
});

Route::redirect('dashboard', '/')->name('dashboard');

require __DIR__.'/fortify.php';
require __DIR__.'/password.php';
require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
