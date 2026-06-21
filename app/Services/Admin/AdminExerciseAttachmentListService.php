<?php

namespace App\Services\Admin;

use App\Models\ExerciseSubmission;
use App\Models\ExerciseSubmissionAttachment;
use App\Services\Course\ExerciseSubmissionAttachmentStorageService;
use App\Support\JalaliDateFormatter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
     *         id: int|string,
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
     *         reviewUrl: string,
     *         isLegacy: bool
     *     }>
     * }
     */
    public function indexForAdmin(): array
    {
        $records = $this->collectActiveAttachmentRows();
        $totalCount = $records->count();
        $totalSizeBytes = (int) $records->sum('sizeBytes');

        $page = max(1, (int) request()->integer('page', 1));
        $perPage = 20;
        $items = $records->forPage($page, $perPage)->values();

        $attachments = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $totalCount,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        return [
            'summary' => [
                'totalCount' => $totalCount,
                'totalSizeBytes' => $totalSizeBytes,
                'totalSizeLabel' => $this->attachments->formatSizeLabel($totalSizeBytes),
            ],
            'attachments' => $attachments,
        ];
    }

    /**
     * @return Collection<int, array{
     *     id: int|string,
     *     studentName: string,
     *     studentMobile: ?string,
     *     submissionTitle: string,
     *     originalName: string,
     *     sizeLabel: string,
     *     sizeBytes: int,
     *     extension: string,
     *     uploadedAtLabel: string,
     *     downloadUrl: string,
     *     deleteUrl: string,
     *     reviewUrl: string,
     *     isLegacy: bool
     * }>
     */
    private function collectActiveAttachmentRows(): Collection
    {
        $rows = collect();

        ExerciseSubmissionAttachment::query()
            ->with(['submission.user'])
            ->whereNull('deleted_at')
            ->latest()
            ->get()
            ->each(function (ExerciseSubmissionAttachment $attachment) use ($rows): void {
                $submission = $attachment->submission;

                $rows->push([
                    'id' => $attachment->id,
                    'studentName' => $submission->user->name,
                    'studentMobile' => $submission->user->mobile,
                    'submissionTitle' => $submission->title,
                    'originalName' => $attachment->original_name,
                    'sizeLabel' => $this->attachments->formatSizeLabel((int) $attachment->size_bytes),
                    'sizeBytes' => (int) $attachment->size_bytes,
                    'extension' => strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION)) ?: '—',
                    'uploadedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($attachment->created_at),
                    'downloadUrl' => route('admin.exercise-submissions.attachments.download', [$submission, $attachment]),
                    'deleteUrl' => route('admin.exercise-submissions.attachments.destroy', [$submission, $attachment]),
                    'reviewUrl' => route('admin.exercise-submissions.show', $submission),
                    'isLegacy' => false,
                ]);
            });

        ExerciseSubmission::query()
            ->with('user')
            ->whereNotNull('attachment_path')
            ->whereNull('attachment_deleted_at')
            ->latest()
            ->get()
            ->each(function (ExerciseSubmission $submission) use ($rows): void {
                $rows->push([
                    'id' => 'legacy-'.$submission->id,
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
                    'isLegacy' => true,
                ]);
            });

        return $rows->sortByDesc(fn (array $row): string => $row['uploadedAtLabel'])->values();
    }
}
