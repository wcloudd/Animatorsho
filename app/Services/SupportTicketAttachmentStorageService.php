<?php

namespace App\Services;

use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketAttachmentStorageService
{
    private const DISK = 'local';

    private const PATH_PREFIX = 'support-attachments/';

    public function store(SupportTicketMessage $message, UploadedFile $file): SupportTicketAttachment
    {
        $extension = $this->resolveExtension($file);
        $path = sprintf(
            '%s%d/%d/%s.%s',
            self::PATH_PREFIX,
            $message->support_ticket_id,
            $message->id,
            Str::uuid()->toString(),
            $extension,
        );

        Storage::disk(self::DISK)->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        return SupportTicketAttachment::query()->create([
            'support_ticket_message_id' => $message->id,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize() ?: 0,
        ]);
    }

    public function delete(string $path): void
    {
        if ($path === '' || ! $this->isValidPath($path)) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public function streamResponse(SupportTicketAttachment $attachment): StreamedResponse
    {
        $path = $this->validatedPath($attachment);

        if ($path === null || ! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        $filename = $this->safeDownloadFilename($attachment->original_name);

        return Storage::disk(self::DISK)->download($path, $filename, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
        ]);
    }

    /**
     * @return array{
     *     id: int,
     *     originalName: string,
     *     sizeBytes: int,
     *     mimeType: string,
     *     downloadUrl: string
     * }|null
     */
    public function toMessageArray(?SupportTicketAttachment $attachment, string $downloadUrl): ?array
    {
        if ($attachment === null) {
            return null;
        }

        return [
            'id' => $attachment->id,
            'originalName' => $attachment->original_name,
            'sizeBytes' => $attachment->size_bytes,
            'mimeType' => $attachment->mime_type,
            'downloadUrl' => $downloadUrl,
        ];
    }

    public function belongsToTicket(SupportTicketAttachment $attachment, int $ticketId): bool
    {
        $attachment->loadMissing('message');

        return $attachment->message?->support_ticket_id === $ticketId;
    }

    private function resolveExtension(UploadedFile $file): string
    {
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension() ?: 'bin');
        $allowedMimes = config('support.attachment_mimes', ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'zip']);

        if (! in_array($extension, $allowedMimes, true)) {
            throw new \InvalidArgumentException('نوع فایل مجاز نیست.');
        }

        return $extension;
    }

    private function validatedPath(SupportTicketAttachment $attachment): ?string
    {
        if ($attachment->disk !== self::DISK) {
            return null;
        }

        return $this->isValidPath($attachment->path) ? $attachment->path : null;
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
}
