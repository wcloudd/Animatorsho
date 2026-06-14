<?php

namespace App\Services\Admin;

use App\Enums\ExerciseSubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\User;
use App\Services\Course\ExerciseSubmissionAttachmentStorageService;
use App\Services\Course\ExerciseSubmissionPresentation;
use App\Support\ExerciseSubmissionStatusLabels;
use App\Support\JalaliDateFormatter;
use App\Support\SafeStoryTextFormatter;

class AdminExerciseSubmissionService
{
    public function __construct(
        private readonly ExerciseSubmissionAttachmentStorageService $attachments,
    ) {}

    /**
     * @return array{
     *     submission: array{
     *         id: int,
     *         studentName: string,
     *         studentMobile: ?string,
     *         title: string,
     *         description: ?string,
     *         descriptionHtml: string,
     *         status: string,
     *         statusValue: string,
     *         statusTone: string,
     *         submissionLink: ?string,
     *         submissionLinkLabel: ?string,
     *         filePathNote: ?string,
     *         attachment: ?array{
     *             originalName: string,
     *             sizeBytes: int,
     *             sizeLabel: string,
     *             mimeType: string,
     *             extension: string,
     *             downloadUrl: string,
     *             isDeleted: bool
     *         },
     *         adminFeedback: ?string,
     *         submittedAtLabel: string,
     *         reviewedAtLabel: string,
     *         reviewedByName: ?string
     *     },
     *     statusOptions: list<array{value: string, label: string}>
     * }
     */
    public function showForAdmin(ExerciseSubmission $submission): array
    {
        $submission->loadMissing(['user', 'reviewer']);

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

        $attachment = $this->attachments->toAttachmentArray(
            $submission,
            $submission->hasActiveAttachment()
                ? route('admin.exercise-submissions.attachment', $submission)
                : '',
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
                'attachment' => $attachment,
                'adminFeedback' => $submission->admin_feedback,
                'submittedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($submission->created_at),
                'reviewedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($submission->reviewed_at),
                'reviewedByName' => $submission->reviewer?->name,
            ],
            'statusOptions' => ExerciseSubmissionStatusLabels::statusOptions(),
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

        $this->attachments->markDeleted($submission, $admin);

        return $submission->fresh();
    }
}
