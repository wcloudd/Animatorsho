<?php

namespace App\Services\Course;

use App\Models\ExerciseSubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExerciseSubmissionAttachmentStorageService
{
    private const DISK = 'local';

    private const PATH_PREFIX = 'exercise-submissions/';

    public function store(ExerciseSubmission $submission, UploadedFile $file): void
    {
        $extension = $this->resolveExtension($file);
        $path = sprintf(
            '%s%d/%s.%s',
            self::PATH_PREFIX,
            $submission->user_id,
            Str::uuid()->toString(),
            $extension,
        );

        Storage::disk(self::DISK)->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        $submission->update([
            'attachment_disk' => self::DISK,
            'attachment_path' => $path,
            'attachment_original_name' => $file->getClientOriginalName(),
            'attachment_mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'attachment_size_bytes' => $file->getSize() ?: 0,
            'attachment_deleted_at' => null,
            'attachment_deleted_by' => null,
        ]);
    }

    public function deleteFromStorage(?string $path): void
    {
        if ($path === null || $path === '' || ! $this->isValidPath($path)) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public function downloadResponse(ExerciseSubmission $submission): StreamedResponse
    {
        $path = $this->validatedPath($submission);

        if ($path === null || ! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        $filename = $this->safeDownloadFilename($submission->attachment_original_name ?? 'attachment');

        return Storage::disk(self::DISK)->download($path, $filename, [
            'Content-Type' => $submission->attachment_mime_type ?: 'application/octet-stream',
        ]);
    }

    public function markDeleted(ExerciseSubmission $submission, User $deletedBy): void
    {
        $path = $submission->attachment_path;

        if ($path !== null) {
            $this->deleteFromStorage($path);
        }

        $submission->update([
            'attachment_path' => null,
            'attachment_disk' => null,
            'attachment_deleted_at' => now(),
            'attachment_deleted_by' => $deletedBy->id,
        ]);
    }

    public function validatedPath(ExerciseSubmission $submission): ?string
    {
        if (! $submission->hasActiveAttachment()) {
            return null;
        }

        if ($submission->attachment_disk !== self::DISK) {
            return null;
        }

        $path = $submission->attachment_path;

        return $path !== null && $this->isValidPath($path) ? $path : null;
    }

    /**
     * @return array{
     *     originalName: string,
     *     sizeBytes: int,
     *     sizeLabel: string,
     *     mimeType: string,
     *     extension: string,
     *     downloadUrl: string,
     *     isDeleted: bool
     * }|null
     */
    public function toAttachmentArray(ExerciseSubmission $submission, string $downloadUrl): ?array
    {
        if ($submission->attachment_deleted_at !== null) {
            return [
                'originalName' => $submission->attachment_original_name ?? '—',
                'sizeBytes' => (int) ($submission->attachment_size_bytes ?? 0),
                'sizeLabel' => $this->formatSizeLabel((int) ($submission->attachment_size_bytes ?? 0)),
                'mimeType' => $submission->attachment_mime_type ?? '—',
                'extension' => $this->extensionFromName($submission->attachment_original_name),
                'downloadUrl' => '',
                'isDeleted' => true,
            ];
        }

        if (! $submission->hasActiveAttachment()) {
            return null;
        }

        return [
            'originalName' => $submission->attachment_original_name ?? 'فایل تمرین',
            'sizeBytes' => (int) ($submission->attachment_size_bytes ?? 0),
            'sizeLabel' => $this->formatSizeLabel((int) ($submission->attachment_size_bytes ?? 0)),
            'mimeType' => $submission->attachment_mime_type ?? 'application/octet-stream',
            'extension' => $this->extensionFromName($submission->attachment_original_name),
            'downloadUrl' => $downloadUrl,
            'isDeleted' => false,
        ];
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
}
