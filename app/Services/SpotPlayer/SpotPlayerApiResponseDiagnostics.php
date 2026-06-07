<?php

namespace App\Services\SpotPlayer;

class SpotPlayerApiResponseDiagnostics
{
    private const PREVIEW_MAX_LENGTH = 300;

    /**
     * @return array{
     *     last_api_error: string,
     *     spotplayer_error_message: ?string,
     *     spotplayer_response_keys: list<string>,
     *     spotplayer_response_preview: ?string
     * }
     */
    public static function fromJsonBody(mixed $body, ?string $fallbackMessage = null): array
    {
        $responseKeys = is_array($body) ? array_values(array_map(
            fn (mixed $key): string => is_string($key) || is_int($key) ? (string) $key : 'unknown',
            array_keys($body),
        )) : [];

        $spotplayerErrorMessage = self::extractSpotPlayerErrorMessage($body);
        $lastApiError = $spotplayerErrorMessage
            ?? self::extractTopLevelMessage($body)
            ?? $fallbackMessage
            ?? 'SpotPlayer returned an error response.';

        return [
            'last_api_error' => self::sanitizeString($lastApiError),
            'spotplayer_error_message' => $spotplayerErrorMessage !== null
                ? self::sanitizeString($spotplayerErrorMessage)
                : null,
            'spotplayer_response_keys' => $responseKeys,
            'spotplayer_response_preview' => self::buildPreview($body),
        ];
    }

    public static function extractSpotPlayerErrorMessage(mixed $body): ?string
    {
        if (! is_array($body)) {
            return null;
        }

        if (isset($body['ex']) && is_array($body['ex'])) {
            $exMessage = $body['ex']['msg'] ?? $body['ex']['message'] ?? null;

            if (is_string($exMessage) && $exMessage !== '') {
                return $exMessage;
            }
        }

        return self::extractTopLevelMessage($body);
    }

    private static function extractTopLevelMessage(mixed $body): ?string
    {
        if (! is_array($body)) {
            return null;
        }

        foreach (['message', 'error', 'msg'] as $key) {
            if (isset($body[$key]) && is_string($body[$key]) && $body[$key] !== '') {
                return $body[$key];
            }
        }

        return null;
    }

    private static function buildPreview(mixed $body): ?string
    {
        if (! is_array($body) || $body === []) {
            return null;
        }

        try {
            $encoded = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        $encoded = self::sanitizeString($encoded);

        if ($encoded === '') {
            return null;
        }

        return mb_substr($encoded, 0, self::PREVIEW_MAX_LENGTH);
    }

    private static function sanitizeString(string $value): string
    {
        $apiKey = config('spotplayer.api_key');

        if (is_string($apiKey) && $apiKey !== '') {
            $value = str_replace($apiKey, '[redacted]', $value);
        }

        if (SpotPlayerMetaSanitizer::containsSecret($value)) {
            return 'SpotPlayer provisioning failed.';
        }

        return $value;
    }
}
