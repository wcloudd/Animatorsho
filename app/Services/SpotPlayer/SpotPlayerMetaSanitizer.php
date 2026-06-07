<?php

namespace App\Services\SpotPlayer;

class SpotPlayerMetaSanitizer
{
    /** @var list<string> */
    private const STORAGE_ALLOWED_KEYS = [
        'provisioned_via',
        'spotplayer_license_id',
        'spotplayer_url',
        'last_api_attempt_at',
        'last_api_error',
        'last_api_http_status',
        'spotplayer_error_message',
        'spotplayer_response_keys',
        'spotplayer_response_preview',
    ];

    /** @var list<string> */
    private const PROFILE_ALLOWED_KEYS = [
        'provisioned_via',
        'spotplayer_license_id',
        'spotplayer_url',
        'last_api_attempt_at',
        'last_api_error',
        'last_api_http_status',
    ];

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public static function sanitize(array $meta): array
    {
        return self::filterAllowedKeys($meta, self::STORAGE_ALLOWED_KEYS);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public static function sanitizeForProfile(array $meta): array
    {
        return self::filterAllowedKeys($meta, self::PROFILE_ALLOWED_KEYS);
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

    /**
     * @param  array<string, mixed>  $meta
     * @param  list<string>  $allowed
     * @return array<string, mixed>
     */
    private static function filterAllowedKeys(array $meta, array $allowed): array
    {
        $sanitized = [];

        foreach ($allowed as $key) {
            if (! array_key_exists($key, $meta)) {
                continue;
            }

            $value = $meta[$key];

            if ($value === null) {
                continue;
            }

            if ($key === 'spotplayer_response_keys') {
                if (! is_array($value)) {
                    continue;
                }

                $keys = array_values(array_filter(
                    $value,
                    fn (mixed $item): bool => is_string($item) && $item !== '' && ! self::containsSecret($item),
                ));

                if ($keys !== []) {
                    $sanitized[$key] = $keys;
                }

                continue;
            }

            if (! is_string($value) && ! is_int($value)) {
                continue;
            }

            if (is_string($value) && self::containsSecret($value)) {
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
