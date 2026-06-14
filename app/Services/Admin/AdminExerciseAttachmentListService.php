<?php

namespace App\Services\Admin;

use App\Models\ExerciseSubmission;
use App\Services\Course\ExerciseSubmissionAttachmentStorageService;
use App\Support\JalaliDateFormatter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminExerciseAttachmentListService
{
    public function __construct(
        private readonly ExerciseSubmissionAttachmentStorageService $attachments,
    ) {}

    /**
     * @return array{
     *     summary: array{
     *         totalCount: int,
     *         totalSizeBytes: int,
     *         totalSizeLabel: string
     *     },
     *     attachments: LengthAwarePaginator<int, array{
     *         id: int,
     *         studentName: string,
     *         studentMobile: ?string,
     *         submissionTitle: string,
     *         originalName: string,
     *         sizeLabel: string,
     *         sizeBytes: int,
     *         extension: string,
     *         uploadedAtLabel: string,
     *         downloadUrl: string,
     *         deleteUrl: string,
     *         reviewUrl: string
     *     }>
     * }
     */
    public function indexForAdmin(): array
    {
        $activeQuery = ExerciseSubmission::query()
            ->with('user')
            ->whereNotNull('attachment_path')
            ->whereNull('attachment_deleted_at');

        $totalCount = (clone $activeQuery)->count();
        $totalSizeBytes = (int) (clone $activeQuery)->sum('attachment_size_bytes');

        $attachments = $activeQuery
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ExerciseSubmission $submission) => [
                'id' => $submission->id,
                'studentName' => $submission->user->name,
                'studentMobile' => $submission->user->mobile,
                'submissionTitle' => $submission->title,
                'originalName' => $submission->attachment_original_name ?? 'فایل تمرین',
                'sizeLabel' => $this->attachments->formatSizeLabel((int) ($submission->attachment_size_bytes ?? 0)),
                'sizeBytes' => (int) ($submission->attachment_size_bytes ?? 0),
                'extension' => strtolower(pathinfo((string) $submission->attachment_original_name, PATHINFO_EXTENSION)) ?: '—',
                'uploadedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($submission->created_at),
                'downloadUrl' => route('admin.exercise-submissions.attachment', $submission),
                'deleteUrl' => route('admin.exercise-submissions.attachment.destroy', $submission),
                'reviewUrl' => route('admin.exercise-submissions.show', $submission),
            ]);

        return [
            'summary' => [
                'totalCount' => $totalCount,
                'totalSizeBytes' => $totalSizeBytes,
                'totalSizeLabel' => $this->attachments->formatSizeLabel($totalSizeBytes),
            ],
            'attachments' => $attachments,
        ];
    }
}
