<?php

use App\Http\Controllers\Admin\AdminStudentNotificationController;
use App\Http\Controllers\Admin\ConsultationController;
use App\Http\Controllers\Admin\CoursePackageController;
use App\Http\Controllers\Admin\CourseResourceController;
use App\Http\Controllers\Admin\CourseUpdateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExerciseAttachmentController;
use App\Http\Controllers\Admin\ExerciseSubmissionController;
use App\Http\Controllers\Admin\ExerciseSubmissionFeedbackAttachmentController;
use App\Http\Controllers\Admin\InstallmentController;
use App\Http\Controllers\Admin\ManualEnrollmentController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SecurityEventController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\SpotPlayerLicenseController;
use App\Http\Controllers\Admin\StudentMedalController;
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

        Route::get('course-updates', [CourseUpdateController::class, 'index'])->name('course-updates.index');
        Route::get('course-updates/create', [CourseUpdateController::class, 'create'])->name('course-updates.create');
        Route::post('course-updates', [CourseUpdateController::class, 'store'])->name('course-updates.store');
        Route::get('course-updates/{courseUpdate}/edit', [CourseUpdateController::class, 'edit'])->name('course-updates.edit');
        Route::patch('course-updates/{courseUpdate}', [CourseUpdateController::class, 'update'])->name('course-updates.update');

        Route::get('course-resources', [CourseResourceController::class, 'index'])->name('course-resources.index');
        Route::get('course-resources/create', [CourseResourceController::class, 'create'])->name('course-resources.create');
        Route::post('course-resources', [CourseResourceController::class, 'store'])->name('course-resources.store');
        Route::get('course-resources/{courseResource}/edit', [CourseResourceController::class, 'edit'])->name('course-resources.edit');
        Route::patch('course-resources/{courseResource}', [CourseResourceController::class, 'update'])->name('course-resources.update');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::patch('orders/{order}/customer', [OrderController::class, 'updateCustomer'])->name('orders.update-customer');
        Route::post('orders/{order}/mark-paid', [OrderController::class, 'markPaid'])->name('orders.mark-paid');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        Route::get('manual-enrollments', [ManualEnrollmentController::class, 'index'])->name('manual-enrollments.index');
        Route::get('manual-enrollments/lookup', [ManualEnrollmentController::class, 'lookup'])->name('manual-enrollments.lookup');
        Route::get('manual-enrollments/user-suggestions', [ManualEnrollmentController::class, 'userSuggestions'])->name('manual-enrollments.user-suggestions');
        Route::post('manual-enrollments', [ManualEnrollmentController::class, 'store'])->name('manual-enrollments.store');
        Route::patch('manual-enrollments/users/{user}', [ManualEnrollmentController::class, 'updateUser'])->name('manual-enrollments.users.update');

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

        Route::get('security-events', [SecurityEventController::class, 'index'])->name('security-events.index');

        Route::get('site-settings', [SiteSettingsController::class, 'index'])->name('site-settings.index');
        Route::patch('site-settings', [SiteSettingsController::class, 'update'])->name('site-settings.update');

        Route::get('consultations', [ConsultationController::class, 'index'])->name('consultations.index');
        Route::patch('consultations/{consultation}', [ConsultationController::class, 'update'])->name('consultations.update');

        Route::get('exercise-submissions', [ExerciseSubmissionController::class, 'index'])->name('exercise-submissions.index');
        Route::get('exercise-submissions/{exerciseSubmission}', [ExerciseSubmissionController::class, 'show'])->name('exercise-submissions.show');
        Route::patch('exercise-submissions/{exerciseSubmission}', [ExerciseSubmissionController::class, 'update'])->name('exercise-submissions.update');
        Route::get('exercise-submissions/{exerciseSubmission}/attachment', [ExerciseSubmissionController::class, 'attachment'])->name('exercise-submissions.attachment');
        Route::delete('exercise-submissions/{exerciseSubmission}/attachment', [ExerciseSubmissionController::class, 'destroyAttachment'])->name('exercise-submissions.attachment.destroy');
        Route::get('exercise-submissions/{exerciseSubmission}/attachments/{attachment}', [ExerciseSubmissionController::class, 'downloadAttachment'])->name('exercise-submissions.attachments.download');
        Route::delete('exercise-submissions/{exerciseSubmission}/attachments/{attachment}', [ExerciseSubmissionController::class, 'destroyAttachmentRecord'])->name('exercise-submissions.attachments.destroy');

        Route::post('exercise-submissions/{exerciseSubmission}/feedback-attachments', [ExerciseSubmissionFeedbackAttachmentController::class, 'store'])->name('exercise-submissions.feedback-attachments.store');
        Route::get('exercise-submissions/{exerciseSubmission}/feedback-attachments/{feedbackAttachment}', [ExerciseSubmissionFeedbackAttachmentController::class, 'download'])->name('exercise-submissions.feedback-attachments.download');
        Route::delete('exercise-submissions/{exerciseSubmission}/feedback-attachments/{feedbackAttachment}', [ExerciseSubmissionFeedbackAttachmentController::class, 'destroy'])->name('exercise-submissions.feedback-attachments.destroy');

        Route::get('exercise-attachments', [ExerciseAttachmentController::class, 'index'])->name('exercise-attachments.index');

        Route::get('student-medals', [StudentMedalController::class, 'index'])->name('student-medals.index');
        Route::post('student-medals', [StudentMedalController::class, 'store'])->name('student-medals.store');
        Route::delete('student-medals/{studentMedalAward}', [StudentMedalController::class, 'destroy'])->name('student-medals.destroy');

        Route::get('student-notifications', [AdminStudentNotificationController::class, 'index'])->name('student-notifications.index');
        Route::post('student-notifications', [AdminStudentNotificationController::class, 'store'])->name('student-notifications.store');

        Route::get('support', [SupportTicketController::class, 'index'])->name('support.index');
        Route::get('support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
        Route::post('support/{ticket}/messages', [SupportTicketController::class, 'storeMessage'])->name('support.messages.store');
        Route::get('support/{ticket}/attachments/{attachment}', [SupportTicketController::class, 'downloadAttachment'])->name('support.attachments.download');
        Route::post('support/{ticket}/close', [SupportTicketController::class, 'close'])->name('support.close');
        Route::post('support/{ticket}/reopen', [SupportTicketController::class, 'reopen'])->name('support.reopen');
    });
