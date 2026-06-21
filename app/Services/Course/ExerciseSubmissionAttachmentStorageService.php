<?php

namespace App\Services\Course;

use App\Models\ExerciseSubmission;
use App\Models\ExerciseSubmissionAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExerciseSubmissionAttachmentStorageService
{
    private const DISK = 'local';

    private const PATH_PREFIX = 'exercise-submissions/';

    /**
     * @param  list<UploadedFile>  $files
     */
    public function storeMany(ExerciseSubmission $submission, array $files): void
    {
        if ($files === []) {
            throw new InvalidArgumentException('حداقل یک فایل تمرین لازم است.');
        }

        DB::transaction(function () use ($submission, $files): void {
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $this->storeUploadedFile($submission, $file);
            }
        });
    }

    public function storeUploadedFile(ExerciseSubmission $submission, UploadedFile $file): ExerciseSubmissionAttachment
    {
        $extension = $this->resolveExtension($file);
        $path = sprintf(
            '%s%d/%d/%s.%s',
            self::PATH_PREFIX,
            $submission->user_id,
            $submission->id,
            Str::uuid()->toString(),
            $extension,
        );

        Storage::disk(self::DISK)->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        return ExerciseSubmissionAttachment::query()->create([
            'exercise_submission_id' => $submission->id,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize() ?: 0,
        ]);
    }

    /**
     * @deprecated Legacy single-file column storage. Kept for backward compatibility.
     */
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

    public function downloadResponseForAttachment(ExerciseSubmissionAttachment $attachment): StreamedResponse
    {
        $path = $this->validatedAttachmentPath($attachment);

        if ($path === null || ! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        $filename = $this->safeDownloadFilename($attachment->original_name);

        return Storage::disk(self::DISK)->download($path, $filename, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function downloadResponse(ExerciseSubmission $submission): StreamedResponse
    {
        $path = $this->validatedLegacyPath($submission);

        if ($path === null || ! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        $filename = $this->safeDownloadFilename($submission->attachment_original_name ?? 'attachment');

        return Storage::disk(self::DISK)->download($path, $filename, [
            'Content-Type' => $submission->attachment_mime_type ?: 'application/octet-stream',
        ]);
    }

    public function markAttachmentDeleted(ExerciseSubmissionAttachment $attachment, User $deletedBy): ExerciseSubmissionAttachment
    {
        if (! $attachment->isActive()) {
            return $attachment;
        }

        $this->deleteFromStorage($attachment->path);

        $attachment->update([
            'deleted_at' => now(),
            'deleted_by' => $deletedBy->id,
        ]);

        return $attachment->fresh();
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

    public function validatedAttachmentPath(ExerciseSubmissionAttachment $attachment): ?string
    {
        if (! $attachment->isActive()) {
            return null;
        }

        if ($attachment->disk !== self::DISK) {
            return null;
        }

        $path = $attachment->path;

        return $path !== null && $this->isValidPath($path) ? $path : null;
    }

    public function validatedLegacyPath(ExerciseSubmission $submission): ?string
    {
        if ($submission->attachment_path === null
            || $submission->attachment_disk === null
            || $submission->attachment_deleted_at !== null) {
            return null;
        }

        if ($submission->attachment_disk !== self::DISK) {
            return null;
        }

        $path = $submission->attachment_path;

        return $path !== null && $this->isValidPath($path) ? $path : null;
    }

    /**
     * @return list<array{
     *     id: int|null,
     *     originalName: string,
     *     sizeBytes: int,
     *     sizeLabel: string,
     *     mimeType: string,
     *     extension: string,
     *     downloadUrl: string,
     *     deleteUrl: string|null,
     *     isDeleted: bool,
     *     isLegacy: bool
     * }>
     */
    public function attachmentsForPresentation(
        ExerciseSubmission $submission,
        string $studentDownloadRouteName,
        ?string $adminDownloadRouteName = null,
        ?string $adminDeleteRouteName = null,
    ): array {
        $items = [];

        $submission->loadMissing('attachments');

        foreach ($submission->attachments as $attachment) {
            $items[] = $this->toAttachmentModelArray(
                $attachment,
                $attachment->isActive()
                    ? route($studentDownloadRouteName, [$submission, $attachment])
                    : '',
                $adminDownloadRouteName !== null && $attachment->isActive()
                    ? route($adminDownloadRouteName, [$submission, $attachment])
                    : '',
                $adminDeleteRouteName !== null && $attachment->isActive()
                    ? route($adminDeleteRouteName, [$submission, $attachment])
                    : null,
            );
        }

        if ($submission->attachmentWasDeleted()) {
            $items[] = [
                'id' => null,
                'originalName' => $submission->attachment_original_name ?? '—',
                'sizeBytes' => (int) ($submission->attachment_size_bytes ?? 0),
                'sizeLabel' => $this->formatSizeLabel((int) ($submission->attachment_size_bytes ?? 0)),
                'mimeType' => $submission->attachment_mime_type ?? '—',
                'extension' => $this->extensionFromName($submission->attachment_original_name),
                'downloadUrl' => '',
                'deleteUrl' => null,
                'isDeleted' => true,
                'isLegacy' => true,
            ];
        } elseif ($submission->attachment_path !== null
            && $submission->attachment_disk !== null
            && $submission->attachment_deleted_at === null) {
            $items[] = [
                'id' => null,
                'originalName' => $submission->attachment_original_name ?? 'فایل تمرین',
                'sizeBytes' => (int) ($submission->attachment_size_bytes ?? 0),
                'sizeLabel' => $this->formatSizeLabel((int) ($submission->attachment_size_bytes ?? 0)),
                'mimeType' => $submission->attachment_mime_type ?? 'application/octet-stream',
                'extension' => $this->extensionFromName($submission->attachment_original_name),
                'downloadUrl' => route('course.exercises.attachment', $submission),
                'deleteUrl' => $adminDeleteRouteName !== null
                    ? route('admin.exercise-submissions.attachment.destroy', $submission)
                    : null,
                'isDeleted' => false,
                'isLegacy' => true,
            ];
        }

        return $items;
    }

    /**
     * @return array{
     *     id: int|null,
     *     originalName: string,
     *     sizeBytes: int,
     *     sizeLabel: string,
     *     mimeType: string,
     *     extension: string,
     *     downloadUrl: string,
     *     deleteUrl: string|null,
     *     isDeleted: bool,
     *     isLegacy: bool
     * }
     */
    public function toAttachmentModelArray(
        ExerciseSubmissionAttachment $attachment,
        string $downloadUrl,
        string $adminDownloadUrl = '',
        ?string $deleteUrl = null,
    ): array {
        if ($attachment->wasDeleted()) {
            return [
                'id' => $attachment->id,
                'originalName' => $attachment->original_name,
                'sizeBytes' => (int) $attachment->size_bytes,
                'sizeLabel' => $this->formatSizeLabel((int) $attachment->size_bytes),
                'mimeType' => $attachment->mime_type ?? '—',
                'extension' => $this->extensionFromName($attachment->original_name),
                'downloadUrl' => '',
                'deleteUrl' => null,
                'isDeleted' => true,
                'isLegacy' => false,
            ];
        }

        return [
            'id' => $attachment->id,
            'originalName' => $attachment->original_name,
            'sizeBytes' => (int) $attachment->size_bytes,
            'sizeLabel' => $this->formatSizeLabel((int) $attachment->size_bytes),
            'mimeType' => $attachment->mime_type ?? 'application/octet-stream',
            'extension' => $this->extensionFromName($attachment->original_name),
            'downloadUrl' => $downloadUrl,
            'deleteUrl' => $deleteUrl,
            'isDeleted' => false,
            'isLegacy' => false,
        ];
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
     *
     * @deprecated Use attachmentsForPresentation() for multi-file support.
     */
    public function toAttachmentArray(ExerciseSubmission $submission, string $downloadUrl): ?array
    {
        $attachments = $this->attachmentsForPresentation($submission, 'course.exercises.attachments.download');
        $active = collect($attachments)->first(fn (array $item): bool => ! $item['isDeleted']);

        if ($active === null) {
            $deleted = collect($attachments)->first(fn (array $item): bool => $item['isDeleted']);

            if ($deleted === null) {
                return null;
            }

            return [
                'originalName' => $deleted['originalName'],
                'sizeBytes' => $deleted['sizeBytes'],
                'sizeLabel' => $deleted['sizeLabel'],
                'mimeType' => $deleted['mimeType'],
                'extension' => $deleted['extension'],
                'downloadUrl' => '',
                'isDeleted' => true,
            ];
        }

        return [
            'originalName' => $active['originalName'],
            'sizeBytes' => $active['sizeBytes'],
            'sizeLabel' => $active['sizeLabel'],
            'mimeType' => $active['mimeType'],
            'extension' => $active['extension'],
            'downloadUrl' => $active['downloadUrl'] !== '' ? $active['downloadUrl'] : $downloadUrl,
            'isDeleted' => false,
        ];
    }

    /**
     * @return Collection<int, ExerciseSubmissionAttachment>
     */
    public function activeAttachmentRecords(): Collection
    {
        return ExerciseSubmissionAttachment::query()
            ->with(['submission.user'])
            ->whereNull('deleted_at')
            ->latest()
            ->get();
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
