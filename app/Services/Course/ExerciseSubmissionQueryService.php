<?php

namespace App\Services\Course;

use App\Enums\ExerciseSubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\User;
use App\Support\ExerciseSubmissionStatusLabels;
use App\Support\JalaliDateFormatter;
use App\Support\SafeStoryTextFormatter;

class ExerciseSubmissionQueryService
{
    public function __construct(
        private readonly ExerciseSubmissionAttachmentStorageService $attachments,
        private readonly ExerciseSubmissionFeedbackStorageService $feedbackAttachments,
    ) {}

    /**
     * @return array{
     *     submissions: list<array{
     *         id: int,
     *         title: string,
     *         description: ?string,
     *         descriptionPreview: string,
     *         descriptionHtml: string,
     *         status: string,
     *         statusLabel: string,
     *         statusTone: string,
     *         submissionLink: ?string,
     *         submissionLinkLabel: ?string,
     *         attachments: list<array{
     *             id: int|null,
     *             originalName: string,
     *             sizeBytes: int,
     *             sizeLabel: string,
     *             mimeType: string,
     *             extension: string,
     *             downloadUrl: string,
     *             deleteUrl: string|null,
     *             isDeleted: bool,
     *             isLegacy: bool
     *         }>,
     *         attachment: ?array{
     *             originalName: string,
     *             sizeBytes: int,
     *             sizeLabel: string,
     *             mimeType: string,
     *             extension: string,
     *             downloadUrl: string,
     *             isDeleted: bool
     *         },
     *         feedbackAttachments: list<array{id: int, originalName: string, sizeLabel: string, downloadUrl: string}>,
     *         adminFeedback: ?string,
     *         submittedAt: ?string,
     *         submittedAtLabel: string,
     *         reviewedAt: ?string,
     *         reviewedAtLabel: string
     *     }>,
     *     createUrl: string
     * }
     */
    public function indexForUser(User $user): array
    {
        $submissions = ExerciseSubmission::query()
            ->with(['attachments', 'feedbackAttachments', 'xpEvent'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (ExerciseSubmission $submission) => $this->toListItem($submission))
            ->values()
            ->all();

        return [
            'submissions' => $submissions,
            'createUrl' => route('course.exercises.create'),
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     pending: int,
     *     latest: ?array{
     *         title: string,
     *         status: string,
     *         statusLabel: string,
     *         statusTone: string
     *     },
     *     exercisesIndexUrl: string,
     *     createUrl: string
     * }
     */
    public function summaryForHome(User $user): array
    {
        $submissions = ExerciseSubmission::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $latest = $submissions->first();

        return [
            'total' => $submissions->count(),
            'pending' => $submissions
                ->filter(fn (ExerciseSubmission $submission) => $submission->status->isPendingReview())
                ->count(),
            'latest' => $latest !== null ? [
                'title' => $latest->title,
                'status' => $latest->status->value,
                'statusLabel' => ExerciseSubmissionStatusLabels::status($latest->status),
                'statusTone' => ExerciseSubmissionStatusLabels::studentStatusTone($latest->status),
            ] : null,
            'exercisesIndexUrl' => route('course.exercises.index'),
            'createUrl' => route('course.exercises.create'),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     description: ?string,
     *     descriptionPreview: string,
     *     descriptionHtml: string,
     *     status: string,
     *     statusLabel: string,
     *     statusTone: string,
     *     submissionLink: ?string,
     *     submissionLinkLabel: ?string,
     *     attachments: list<array{
     *         id: int|null,
     *         originalName: string,
     *         sizeBytes: int,
     *         sizeLabel: string,
     *         mimeType: string,
     *         extension: string,
     *         downloadUrl: string,
     *         deleteUrl: string|null,
     *         isDeleted: bool,
     *         isLegacy: bool
     *     }>,
     *     attachment: ?array{
     *         originalName: string,
     *         sizeBytes: int,
     *         sizeLabel: string,
     *         mimeType: string,
     *         extension: string,
     *         downloadUrl: string,
     *         isDeleted: bool
     *     },
     *     adminFeedback: ?string,
     *     submittedAt: ?string,
     *     submittedAtLabel: string,
     *     reviewedAt: ?string,
     *     reviewedAtLabel: string
     * }
     */
    public function toListItem(ExerciseSubmission $submission): array
    {
        $attachments = $this->attachments->attachmentsForPresentation(
            $submission,
            'course.exercises.attachments.download',
        );

        $legacyAttachment = $this->attachments->toAttachmentArray(
            $submission,
            $submission->hasActiveAttachment()
                ? route('course.exercises.attachment', $submission)
                : '',
        );

        $feedbackAttachments = $this->feedbackAttachments->forStudentPresentation(
            $submission,
            'course.exercises.feedback-attachments.download',
        );

        return [
            'id' => $submission->id,
            'title' => $submission->title,
            'description' => $submission->description,
            'descriptionPreview' => SafeStoryTextFormatter::toPreview($submission->description),
            'descriptionHtml' => SafeStoryTextFormatter::toHtml($submission->description),
            'status' => $submission->status->value,
            'statusLabel' => ExerciseSubmissionStatusLabels::status($submission->status),
            'statusTone' => ExerciseSubmissionStatusLabels::studentStatusTone($submission->status),
            'submissionLink' => ExerciseSubmissionPresentation::publicSubmissionLink(
                $submission->submission_url,
                $submission->file_path,
            ),
            'submissionLinkLabel' => ExerciseSubmissionPresentation::submissionLinkLabel(
                $submission->submission_url,
                $submission->file_path,
                $submission->hasActiveAttachment(),
            ),
            'attachments' => $attachments,
            'attachment' => $legacyAttachment,
            'feedbackAttachments' => $feedbackAttachments,
            'awardedXp' => $submission->status === ExerciseSubmissionStatus::Approved
                ? ($submission->xpEvent?->points)
                : null,
            'adminFeedback' => $submission->admin_feedback,
            'submittedAt' => $submission->created_at?->toIso8601String(),
            'submittedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($submission->created_at),
            'reviewedAt' => $submission->reviewed_at?->toIso8601String(),
            'reviewedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($submission->reviewed_at),
        ];
    }
}
