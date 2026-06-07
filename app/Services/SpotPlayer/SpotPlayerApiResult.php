<?php

namespace App\Services\SpotPlayer;

class SpotPlayerApiResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $licenseKey = null,
        public readonly ?string $externalId = null,
        public readonly ?string $url = null,
        public readonly ?string $errorMessage = null,
        public readonly ?int $httpStatus = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $errorDiagnostics = null,
    ) {}

    public static function success(string $licenseKey, ?string $externalId, ?string $url, ?int $httpStatus = null): self
    {
        return new self(
            successful: true,
            licenseKey: $licenseKey,
            externalId: $externalId,
            url: $url,
            httpStatus: $httpStatus,
        );
    }

    /**
     * @param  array<string, mixed>|null  $errorDiagnostics
     */
    public static function failure(string $errorMessage, ?int $httpStatus = null, ?array $errorDiagnostics = null): self
    {
        return new self(
            successful: false,
            errorMessage: $errorMessage,
            httpStatus: $httpStatus,
            errorDiagnostics: $errorDiagnostics,
        );
    }
}
