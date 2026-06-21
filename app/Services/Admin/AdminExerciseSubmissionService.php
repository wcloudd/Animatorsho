<?php

namespace App\Services\Admin;

use App\Enums\ExerciseSubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\ExerciseSubmissionAttachment;
use App\Models\User;
use App\Services\Course\ExerciseSubmissionAttachmentStorageService;
use App\Services\Course\ExerciseSubmissionFeedbackStorageService;
use App\Services\Course\ExerciseSubmissionPresentation;
use App\Services\StudentNotificationService;
use App\Services\StudentXpService;
use App\Support\ExerciseSubmissionStatusLabels;
use App\Support\JalaliDateFormatter;
use App\Support\SafeStoryTextFormatter;

class AdminExerciseSubmissionService
{
    public function __construct(
        private readonly ExerciseSubmissionAttachmentStorageService $attachments,
        private readonly ExerciseSubmissionFeedbackStorageService $feedbackAttachments,
        private readonly StudentXpService $xpService,
        private readonly StudentNotificationService $notificationService,
    ) {}

    public function showForAdmin(ExerciseSubmission $submission): array
    {
        $submission->loadMissing(['user', 'reviewer', 'attachments', 'feedbackAttachments', 'xpEvent']);

        $publicLink = ExerciseSubmissionPresentation::publicSubmissionLink(
            $submission->submission_url,
            $submission->file_path,
        );

        $filePathNote = null;
        if ($submission->file_path !== null
            && $submission->file_path !== ''
            && $publicLink === null
            && ! $submission->hasActiveAttachment()
            && ! $submission->attachmentWasDeleted()) {
            $filePathNote = 'فایل با مسیر داخلی ثبت شده است.';
        }

        $attachments = $this->attachments->attachmentsForPresentation(
            $submission,
            'course.exercises.attachments.download',
            'admin.exercise-submissions.attachments.download',
            'admin.exercise-submissions.attachments.destroy',
        );

        $legacyAttachment = $this->attachments->toAttachmentArray(
            $submission,
            $submission->hasActiveAttachment()
                ? route('admin.exercise-submissions.attachment', $submission)
                : '',
        );

        $feedbackAttachments = $this->feedbackAttachments->forAdminPresentation(
            $submission,
            'admin.exercise-submissions.feedback-attachments.download',
            'admin.exercise-submissions.feedback-attachments.destroy',
        );

        return [
            'submission' => [
                'id' => $submission->id,
                'studentName' => $submission->user->name,
                'studentMobile' => $submission->user->mobile,
                'title' => $submission->title,
                'description' => $submission->description,
                'descriptionHtml' => SafeStoryTextFormatter::toHtml($submission->description),
                'status' => ExerciseSubmissionStatusLabels::status($submission->status),
                'statusValue' => $submission->status->value,
                'statusTone' => ExerciseSubmissionStatusLabels::statusTone($submission->status),
                'submissionLink' => $publicLink,
                'submissionLinkLabel' => ExerciseSubmissionPresentation::submissionLinkLabel(
                    $submission->submission_url,
                    $submission->file_path,
                    $submission->hasActiveAttachment(),
                ),
                'filePathNote' => $filePathNote,
                'attachments' => $attachments,
                'attachment' => $legacyAttachment,
                'feedbackAttachments' => $feedbackAttachments,
                'awardedXp' => $submission->xpEvent?->points,
                'adminFeedback' => $submission->admin_feedback,
                'submittedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($submission->created_at),
                'reviewedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($submission->reviewed_at),
                'reviewedByName' => $submission->reviewer?->name,
            ],
            'statusOptions' => ExerciseSubmissionStatusLabels::statusOptions(),
            'maxFeedbackAttachments' => (int) config('exercise_submissions.max_feedback_attachments_per_submission', 3),
        ];
    }

    /**
     * @param  array{
     *     status: ExerciseSubmissionStatus,
     *     admin_feedback?: ?string,
     *     xp_award?: int|null
     * }  $data
     */
    public function review(ExerciseSubmission $submission, User $admin, array $data): ExerciseSubmission
    {
        $oldStatus = $submission->status;
        $oldFeedback = $submission->admin_feedback;

        $submission->update([
            'status' => $data['status'],
            'admin_feedback' => $data['admin_feedback'] ?? null,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $fresh = $submission->fresh();
        $fresh->loadMissing('user');

        $this->xpService->awardForSubmission($fresh, $admin, (int) ($data['xp_award'] ?? 0));

        $student = $fresh->user;

        if ($student !== null) {
            $newStatus = $fresh->status;
            $newFeedback = $fresh->admin_feedback;

            if ($oldStatus !== $newStatus) {
                $title = match ($newStatus) {
                    ExerciseSubmissionStatus::Approved => 'تمرینت تأیید شد',
                    ExerciseSubmissionStatus::NeedsRevision => 'تمرینت نیاز به اصلاح دارد',
                    ExerciseSubmissionStatus::Reviewing => 'تمرینت در حال بررسی است',
                    default => 'وضعیت تمرینت تغییر کرد',
                };
                $body = $fresh->title !== null && $fresh->title !== ''
                    ? "تمرین «{$fresh->title}» بررسی شد."
                    : null;

                $this->notificationService->upsertForSource(
                    $student,
                    StudentNotificationService::TYPE_EXERCISE_REVIEWED,
                    'exercise_submission',
                    $fresh->id,
                    ['title' => $title, 'body' => $body, 'action_url' => route('course.exercises.index')],
                );
            }

            $hadFeedback = $oldFeedback !== null && $oldFeedback !== '';
            $hasFeedback = $newFeedback !== null && $newFeedback !== '';

            if ($hasFeedback && ! $hadFeedback) {
                $this->notificationService->createOnceForSource(
                    $student,
                    StudentNotificationService::TYPE_TEACHER_FEEDBACK_ADDED,
                    'exercise_submission',
                    $fresh->id,
                    ['title' => 'بازخورد استاد ثبت شد', 'body' => 'استاد برای تمرینت بازخورد گذاشت.', 'action_url' => route('course.exercises.index')],
                );
            }
        }

        return $fresh;
    }

    public function deleteAttachment(ExerciseSubmission $submission, User $admin): ExerciseSubmission
    {
        if (! $submission->hasActiveAttachment()) {
            return $submission;
        }

        if ($submission->attachment_path !== null && $submission->attachment_deleted_at === null) {
            $this->attachments->markDeleted($submission, $admin);

            return $submission->fresh();
        }

        return $submission->fresh();
    }

    public function deleteAttachmentRecord(
        ExerciseSubmission $submission,
        ExerciseSubmissionAttachment $attachment,
        User $admin,
    ): ExerciseSubmissionAttachment {
        if ($attachment->exercise_submission_id !== $submission->id) {
            abort(404);
        }

        return $this->attachments->markAttachmentDeleted($attachment, $admin);
    }
}
