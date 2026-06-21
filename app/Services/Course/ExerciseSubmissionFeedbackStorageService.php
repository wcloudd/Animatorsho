<?php

namespace App\Services\Course;

use App\Models\ExerciseSubmission;
use App\Models\ExerciseSubmissionFeedbackAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExerciseSubmissionFeedbackStorageService
{
    private const DISK = 'local';

    private const PATH_PREFIX = 'exercise-submission-feedback/';

    /**
     * @param  list<UploadedFile>  $files
     */
    public function storeMany(ExerciseSubmission $submission, User $admin, array $files): void
    {
        if ($files === []) {
            throw new InvalidArgumentException('حداقل یک فایل لازم است.');
        }

        $maxFeedback = (int) config('exercise_submissions.max_feedback_attachments_per_submission', 3);
        $existingCount = $submission->feedbackAttachments()->whereNull('deleted_at')->count();

        if ($existingCount + count($files) > $maxFeedback) {
            throw new InvalidArgumentException(
                "حداکثر {$maxFeedback} فایل استاد برای هر تمرین مجاز است."
            );
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $this->storeOne($submission, $admin, $file);
        }
    }

    public function storeOne(ExerciseSubmission $submission, User $admin, UploadedFile $file): ExerciseSubmissionFeedbackAttachment
    {
        $extension = $this->resolveExtension($file);
        $path = sprintf(
            '%s%d/%s.%s',
            self::PATH_PREFIX,
            $submission->id,
            Str::uuid()->toString(),
            $extension,
        );

        Storage::disk(self::DISK)->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        return ExerciseSubmissionFeedbackAttachment::query()->create([
            'exercise_submission_id' => $submission->id,
            'uploaded_by' => $admin->id,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize() ?: 0,
        ]);
    }

    public function downloadResponse(ExerciseSubmissionFeedbackAttachment $attachment): StreamedResponse
    {
        if (! $attachment->isActive()) {
            abort(404);
        }

        if ($attachment->disk !== self::DISK) {
            abort(404);
        }

        $path = $attachment->path;

        if ($path === null || ! $this->isValidPath($path) || ! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        $filename = $this->safeDownloadFilename($attachment->original_name);

        return Storage::disk(self::DISK)->download($path, $filename, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function markDeleted(ExerciseSubmissionFeedbackAttachment $attachment, User $admin): ExerciseSubmissionFeedbackAttachment
    {
        if (! $attachment->isActive()) {
            return $attachment;
        }

        $path = $attachment->path;

        if ($path !== null && $this->isValidPath($path)) {
            Storage::disk(self::DISK)->delete($path);
        }

        $attachment->update([
            'deleted_at' => now(),
            'deleted_by' => $admin->id,
        ]);

        return $attachment->fresh();
    }

    /**
     * @return list<array{
     *     id: int,
     *     originalName: string,
     *     sizeBytes: int,
     *     sizeLabel: string,
     *     extension: string,
     *     downloadUrl: string,
     *     deleteUrl: string|null,
     *     isDeleted: bool
     * }>
     */
    public function forAdminPresentation(
        ExerciseSubmission $submission,
        string $adminDownloadRoute,
        string $adminDeleteRoute,
    ): array {
        $submission->loadMissing('feedbackAttachments');
        $items = [];

        foreach ($submission->feedbackAttachments as $attachment) {
            $items[] = [
                'id' => $attachment->id,
                'originalName' => $attachment->original_name,
                'sizeBytes' => (int) $attachment->size_bytes,
                'sizeLabel' => $this->formatSizeLabel((int) $attachment->size_bytes),
                'extension' => $this->extensionFromName($attachment->original_name),
                'downloadUrl' => $attachment->isActive()
                    ? route($adminDownloadRoute, [$submission, $attachment])
                    : '',
                'deleteUrl' => $attachment->isActive()
                    ? route($adminDeleteRoute, [$submission, $attachment])
                    : null,
                'isDeleted' => ! $attachment->isActive(),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{
     *     id: int,
     *     originalName: string,
     *     sizeLabel: string,
     *     downloadUrl: string
     * }>
     */
    public function forStudentPresentation(
        ExerciseSubmission $submission,
        string $studentDownloadRoute,
    ): array {
        $submission->loadMissing('feedbackAttachments');
        $items = [];

        foreach ($submission->feedbackAttachments as $attachment) {
            if (! $attachment->isActive()) {
                continue;
            }

            $items[] = [
                'id' => $attachment->id,
                'originalName' => $attachment->original_name,
                'sizeLabel' => $this->formatSizeLabel((int) $attachment->size_bytes),
                'downloadUrl' => route($studentDownloadRoute, [$submission, $attachment]),
            ];
        }

        return $items;
    }

    private function resolveExtension(UploadedFile $file): string
    {
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension() ?: 'bin');
        $allowedExtensions = config('exercise_submissions.attachment_extensions', []);

        if (! in_array($extension, $allowedExtensions, true)) {
            throw new InvalidArgumentException('نوع فایل مجاز نیست.');
        }

        return $extension;
    }

    private function isValidPath(string $path): bool
    {
        return $path !== ''
            && ! str_contains($path, '..')
            && str_starts_with($path, self::PATH_PREFIX);
    }

    private function safeDownloadFilename(string $originalName): string
    {
        $basename = basename(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $originalName));
        $sanitized = preg_replace('/[^\p{L}\p{N}\.\-_\s]/u', '_', $basename) ?? 'attachment';

        return $sanitized !== '' ? $sanitized : 'attachment';
    }

    private function extensionFromName(?string $originalName): string
    {
        if ($originalName === null || $originalName === '') {
            return '—';
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : '—';
    }

    public function formatSizeLabel(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' بایت';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' کیلوبایت';
        }

        return number_format($bytes / (1024 * 1024), 1).' مگابایت';
    }
}
