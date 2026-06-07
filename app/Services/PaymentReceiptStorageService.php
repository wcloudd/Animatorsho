<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceiptStorageService
{
    private const DISK = 'local';

    public function isConfigured(): bool
    {
        $cardNumber = $this->cardNumber();
        $cardOwnerName = $this->cardOwnerName();

        return is_string($cardNumber) && $cardNumber !== ''
            && is_string($cardOwnerName) && $cardOwnerName !== '';
    }

    public function cardNumber(): ?string
    {
        $value = config('card_to_card.card_number');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function cardOwnerName(): ?string
    {
        $value = config('card_to_card.card_owner_name');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return array{receipt_path: string, receipt_original_name: string, receipt_mime: string, receipt_uploaded_at: string}
     */
    public function store(Payment $payment, UploadedFile $file): array
    {
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension() ?: 'jpg');
        $allowedMimes = config('card_to_card.receipt_mimes', ['jpg', 'jpeg', 'png', 'webp']);

        if (! in_array($extension, $allowedMimes, true)) {
            $extension = 'jpg';
        }

        $path = sprintf(
            'payment-receipts/%d/%s.%s',
            $payment->id,
            Str::uuid()->toString(),
            $extension,
        );

        Storage::disk(self::DISK)->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        return [
            'receipt_path' => $path,
            'receipt_original_name' => $file->getClientOriginalName(),
            'receipt_mime' => $file->getMimeType() ?? 'application/octet-stream',
            'receipt_uploaded_at' => now()->toIso8601String(),
        ];
    }

    public function delete(string $path): void
    {
        if ($path === '') {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public function hasReceipt(Payment $payment): bool
    {
        $path = $this->receiptPath($payment);

        return $path !== null && Storage::disk(self::DISK)->exists($path);
    }

    public function receiptPath(Payment $payment): ?string
    {
        $meta = $payment->meta;

        if (! is_array($meta)) {
            return null;
        }

        $path = $meta['receipt_path'] ?? null;

        if (! is_string($path) || $path === '' || str_contains($path, '..')) {
            return null;
        }

        if (! str_starts_with($path, 'payment-receipts/')) {
            return null;
        }

        return $path;
    }

    public function streamResponse(Payment $payment): StreamedResponse
    {
        $path = $this->receiptPath($payment);

        if ($path === null || ! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        $meta = $payment->meta;
        $mime = is_array($meta) && is_string($meta['receipt_mime'] ?? null)
            ? $meta['receipt_mime']
            : Storage::disk(self::DISK)->mimeType($path);

        return Storage::disk(self::DISK)->response($path, null, [
            'Content-Type' => $mime ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
        ]);
    }
}
