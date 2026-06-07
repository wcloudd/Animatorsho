<?php

namespace App\Services\SpotPlayer;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpotPlayerApiClient
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createLicense(array $payload): SpotPlayerApiResult
    {
        $apiKey = config('spotplayer.api_key');

        if (! config('spotplayer.enabled')) {
            return SpotPlayerApiResult::failure('SpotPlayer API is disabled.');
        }

        if (! is_string($apiKey) || $apiKey === '') {
            return SpotPlayerApiResult::failure('SpotPlayer API key is not configured.');
        }

        $url = config('spotplayer.api_base_url').'/license/edit/';

        try {
            $response = Http::timeout((int) config('spotplayer.timeout', 15))
                ->acceptJson()
                ->withHeaders([
                    '$API' => $apiKey,
                    '$LEVEL' => '-1',
                ])
                ->post($url, $payload);
        } catch (\Throwable $exception) {
            Log::warning('SpotPlayer license create request failed.', [
                'message' => $exception->getMessage(),
            ]);

            return SpotPlayerApiResult::failure('Could not connect to SpotPlayer.');
        }

        $httpStatus = $response->status();

        if (! $response->successful()) {
            $errorMessage = $this->extractErrorMessage($response->json());

            Log::warning('SpotPlayer license create returned an error response.', [
                'http_status' => $httpStatus,
            ]);

            return SpotPlayerApiResult::failure(
                $errorMessage ?? 'SpotPlayer returned an error response.',
                $httpStatus,
            );
        }

        /** @var array<string, mixed>|null $body */
        $body = $response->json();

        if (! is_array($body)) {
            return SpotPlayerApiResult::failure('SpotPlayer returned an invalid response.', $httpStatus);
        }

        $licenseKey = isset($body['key']) && is_string($body['key']) ? $body['key'] : null;
        $externalId = isset($body['_id']) && is_string($body['_id']) ? $body['_id'] : null;
        $licenseUrl = isset($body['url']) && is_string($body['url']) ? $body['url'] : null;

        if ($licenseKey === null || $licenseKey === '') {
            return SpotPlayerApiResult::failure('SpotPlayer response did not include a license key.', $httpStatus);
        }

        return SpotPlayerApiResult::success($licenseKey, $externalId, $licenseUrl, $httpStatus);
    }

    private function extractErrorMessage(mixed $body): ?string
    {
        if (! is_array($body)) {
            return null;
        }

        foreach (['message', 'error', 'msg'] as $key) {
            if (isset($body[$key]) && is_string($body[$key]) && $body[$key] !== '') {
                return $this->sanitizeErrorMessage($body[$key]);
            }
        }

        return null;
    }

    private function sanitizeErrorMessage(string $message): string
    {
        $apiKey = config('spotplayer.api_key');

        if (is_string($apiKey) && $apiKey !== '') {
            $message = str_replace($apiKey, '[redacted]', $message);
        }

        return mb_substr($message, 0, 500);
    }
}
