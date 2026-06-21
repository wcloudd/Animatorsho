<?php

namespace App\Services\Admin;

use App\Enums\ExerciseSubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\ExerciseSubmissionAttachment;
use App\Models\User;
use App\Services\Course\ExerciseSubmissionAttachmentStorageService;
use App\Services\Course\ExerciseSubmissionFeedbackStorageService;
use App\Services\Course\ExerciseSubmissionPresentation;
use App\Support\ExerciseSubmissionStatusLabels;
use App\Support\JalaliDateFormatter;
use App\Support\SafeStoryTextFormatter;

class AdminExerciseSubmissionService
{
    public function __construct(
        private readonly ExerciseSubmissionAttachmentStorageService $attachments,
        private readonly ExerciseSubmissionFeedbackStorageService $feedbackAttachments,
    ) {}

    public function showForAdmin(ExerciseSubmission $submission): array
    {
        $submission->loadMissing(['user', 'reviewer', 'attachments', 'feedbackAttachments']);

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
     *     admin_feedback?: ?string
     * }  $data
     */
    public function review(ExerciseSubmission $submission, User $admin, array $data): ExerciseSubmission
    {
        $submission->update([
            'status' => $data['status'],
            'admin_feedback' => $data['admin_feedback'] ?? null,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        return $submission->fresh();
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
