<?php

namespace App\Services\Zarinpal;

readonly class ZarinpalVerifyResult
{
    /**
     * @param  array<string, mixed>|null  $rawResponse
     */
    public function __construct(
        public bool $successful,
        public ?string $refId = null,
        public ?string $errorMessage = null,
        public ?array $rawResponse = null,
    ) {}

    public static function success(string $refId, ?array $rawResponse = null): self
    {
        return new self(
            successful: true,
            refId: $refId,
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
