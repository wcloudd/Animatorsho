<?php

use App\Http\Controllers\Admin\CoursePackageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InstallmentController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\SpotPlayerLicenseController;
use App\Http\Controllers\Admin\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('packages', [CoursePackageController::class, 'index'])->name('packages.index');
        Route::get('packages/{package}/edit', [CoursePackageController::class, 'edit'])->name('packages.edit');
        Route::patch('packages/{package}', [CoursePackageController::class, 'update'])->name('packages.update');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::patch('orders/{order}/customer', [OrderController::class, 'updateCustomer'])->name('orders.update-customer');
        Route::post('orders/{order}/mark-paid', [OrderController::class, 'markPaid'])->name('orders.mark-paid');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

        Route::get('installments', [InstallmentController::class, 'index'])->name('installments.index');

        Route::get('licenses', [SpotPlayerLicenseController::class, 'index'])->name('licenses.index');
        Route::post('licenses/{license}/activate', [SpotPlayerLicenseController::class, 'activate'])->name('licenses.activate');
        Route::post('licenses/{license}/retry-provision', [SpotPlayerLicenseController::class, 'retryProvision'])->name('licenses.retry-provision');
        Route::post('licenses/{license}/revoke', [SpotPlayerLicenseController::class, 'revoke'])->name('licenses.revoke');

        Route::get('sms', [SmsController::class, 'index'])->name('sms.index');
        Route::get('sms/logs', [SmsController::class, 'logs'])->name('sms.logs.index');
        Route::patch('sms/settings', [SmsController::class, 'updateSettings'])->name('sms.settings.update');
        Route::patch('sms/templates/{template}', [SmsController::class, 'updateTemplate'])->name('sms.templates.update');

        Route::get('support', [SupportTicketController::class, 'index'])->name('support.index');
        Route::get('support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
        Route::post('support/{ticket}/messages', [SupportTicketController::class, 'storeMessage'])->name('support.messages.store');
        Route::get('support/{ticket}/attachments/{attachment}', [SupportTicketController::class, 'downloadAttachment'])->name('support.attachments.download');
        Route::post('support/{ticket}/close', [SupportTicketController::class, 'close'])->name('support.close');
        Route::post('support/{ticket}/reopen', [SupportTicketController::class, 'reopen'])->name('support.reopen');
    });
