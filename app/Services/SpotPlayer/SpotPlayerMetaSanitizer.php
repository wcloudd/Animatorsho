<?php

namespace App\Services\SpotPlayer;

class SpotPlayerMetaSanitizer
{
    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public static function sanitize(array $meta): array
    {
        $allowed = [
            'provisioned_via',
            'spotplayer_license_id',
            'spotplayer_url',
            'last_api_attempt_at',
            'last_api_error',
            'last_api_http_status',
        ];

        $sanitized = [];

        foreach ($allowed as $key) {
            if (! array_key_exists($key, $meta)) {
                continue;
            }

            $value = $meta[$key];

            if ($value === null) {
                continue;
            }

            if (is_string($value) && self::containsSecret($value)) {
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>|null  $existing
     * @param  array<string, mixed>  $updates
     * @return array<string, mixed>
     */
    public static function merge(?array $existing, array $updates): array
    {
        $merged = array_merge($existing ?? [], $updates);

        foreach ($updates as $key => $value) {
            if ($value === null) {
                unset($merged[$key]);
            }
        }

        return self::sanitize($merged);
    }

    public static function containsSecret(string $value): bool
    {
        $apiKey = config('spotplayer.api_key');

        if (is_string($apiKey) && $apiKey !== '' && str_contains($value, $apiKey)) {
            return true;
        }

        return false;
    }
}
