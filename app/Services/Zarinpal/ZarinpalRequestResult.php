<?php

namespace App\Services\Zarinpal;

readonly class ZarinpalRequestResult
{
    /**
     * @param  array<string, mixed>|null  $rawResponse
     */
    public function __construct(
        public bool $successful,
        public ?string $authority = null,
        public ?string $paymentUrl = null,
        public ?string $errorMessage = null,
        public ?array $rawResponse = null,
    ) {}

    public static function success(string $authority, string $paymentUrl, ?array $rawResponse = null): self
    {
        return new self(
            successful: true,
            authority: $authority,
            paymentUrl: $paymentUrl,
            rawResponse: $rawResponse,
        );
    }

    public static function failure(string $errorMessage, ?array $rawResponse = null): self
    {
        return new self(
            successful: false,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse,
        );
    }
}
