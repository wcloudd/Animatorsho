<?php

namespace App\Support;

use App\Enums\ExternalEnrollmentSource;

class ExternalEnrollmentSourceLabels
{
    public static function label(ExternalEnrollmentSource $source): string
    {
        return match ($source) {
            ExternalEnrollmentSource::Eitaa => 'ایتا',
            ExternalEnrollmentSource::Offline => 'حضوری / خارج از سایت',
            ExternalEnrollmentSource::Manual => 'ثبت دستی',
            ExternalEnrollmentSource::AdminGrant => 'دسترسی ادمین',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (ExternalEnrollmentSource $source): array => [
                'value' => $source->value,
                'label' => self::label($source),
            ],
            ExternalEnrollmentSource::cases(),
        );
    }

    public static function labelFromMeta(?array $meta): ?string
    {
        if (! is_array($meta)) {
            return null;
        }

        $value = $meta['external_source'] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        $source = ExternalEnrollmentSource::tryFrom($value);

        return $source !== null ? self::label($source) : null;
    }
}
