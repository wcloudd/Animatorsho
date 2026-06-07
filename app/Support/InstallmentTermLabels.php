<?php

namespace App\Support;

class InstallmentTermLabels
{
    public static function label(?string $term): ?string
    {
        if ($term === null || $term === '') {
            return null;
        }

        return match ($term) {
            'one_month' => '۱ ماهه',
            'two_months' => '۲ ماهه',
            default => $term,
        };
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public static function fromPaymentMeta(?array $meta): ?string
    {
        if ($meta === null) {
            return null;
        }

        $term = $meta['requested_term'] ?? $meta['installment_term'] ?? null;

        if (! is_string($term)) {
            return null;
        }

        return self::label($term);
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public static function noteFromPaymentMeta(?array $meta): ?string
    {
        if ($meta === null) {
            return null;
        }

        $note = $meta['note'] ?? null;

        if (! is_string($note) || trim($note) === '') {
            return null;
        }

        return trim($note);
    }
}
