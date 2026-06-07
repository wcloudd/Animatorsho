<?php

namespace App\Services\SpotPlayer;

use App\Enums\SpotPlayerLicenseStatus;
use App\Models\SpotPlayerLicense;
use App\Services\Sms\SmsNotifier;
use Illuminate\Support\Facades\Log;

class SpotPlayerApiProvisioningService
{
    public function __construct(
        private readonly SpotPlayerApiClient $apiClient,
        private readonly SpotPlayerLicensePayloadBuilder $payloadBuilder,
        private readonly SmsNotifier $smsNotifier,
    ) {}

    public function attemptForLicense(SpotPlayerLicense $license): SpotPlayerLicense
    {
        $license = $license->fresh(['order', 'coursePackage', 'user']) ?? $license;

        if (! config('spotplayer.enabled')) {
            return $license;
        }

        if ($license->status === SpotPlayerLicenseStatus::Active && is_string($license->license_key) && $license->license_key !== '') {
            return $license;
        }

        if ($license->status !== SpotPlayerLicenseStatus::Pending) {
            return $license;
        }

        $existingExternalId = is_array($license->meta) ? ($license->meta['spotplayer_license_id'] ?? null) : null;

        if (is_string($existingExternalId) && $existingExternalId !== '') {
            return $this->recordFailure(
                $license,
                'SpotPlayer license already exists externally. Activate manually or contact support.',
                null,
                ['last_api_attempt_at' => now()->toIso8601String()],
            );
        }

        $attemptMeta = [
            'last_api_attempt_at' => now()->toIso8601String(),
        ];

        $courseIds = $license->coursePackage?->spotplayer_course_ids;

        if (! is_array($courseIds) || $courseIds === []) {
            return $this->recordFailure($license, 'SpotPlayer course IDs are not configured for this package.', null, $attemptMeta);
        }

        $payload = $this->payloadBuilder->build($license);

        if ($payload === null) {
            return $this->recordFailure($license, 'SpotPlayer payload could not be built for this license.', null, $attemptMeta);
        }

        $result = $this->apiClient->createLicense($payload);

        if (! $result->successful) {
            return $this->recordFailure($license, $result->errorMessage ?? 'SpotPlayer provisioning failed.', $result->httpStatus, $attemptMeta);
        }

        return $this->recordSuccess($license, $result, $attemptMeta);
    }

    /**
     * @param  array<string, mixed>  $attemptMeta
     */
    private function recordSuccess(SpotPlayerLicense $license, SpotPlayerApiResult $result, array $attemptMeta): SpotPlayerLicense
    {
        $license->update([
            'license_key' => $result->licenseKey,
            'status' => SpotPlayerLicenseStatus::Active,
            'activated_at' => now(),
            'meta' => SpotPlayerMetaSanitizer::merge($license->meta, array_merge($attemptMeta, [
                'provisioned_via' => 'api',
                'spotplayer_license_id' => $result->externalId,
                'spotplayer_url' => $result->url,
                'last_api_error' => null,
                'last_api_http_status' => $result->httpStatus,
            ])),
        ]);

        $freshLicense = $license->fresh();

        if ($freshLicense !== null) {
            $this->smsNotifier->notifyLicenseActivated($freshLicense);
        }

        return $freshLicense ?? $license;
    }

    /**
     * @param  array<string, mixed>  $attemptMeta
     */
    private function recordFailure(
        SpotPlayerLicense $license,
        string $errorMessage,
        ?int $httpStatus,
        array $attemptMeta,
    ): SpotPlayerLicense {
        Log::warning('SpotPlayer license provisioning failed.', [
            'license_id' => $license->id,
            'order_id' => $license->order_id,
            'http_status' => $httpStatus,
        ]);

        $license->update([
            'meta' => SpotPlayerMetaSanitizer::merge($license->meta, array_merge($attemptMeta, [
                'last_api_error' => SpotPlayerMetaSanitizer::containsSecret($errorMessage)
                    ? 'SpotPlayer provisioning failed.'
                    : $errorMessage,
                'last_api_http_status' => $httpStatus,
            ])),
        ]);

        return $license->fresh() ?? $license;
    }
}
