<?php

namespace App\Support\Security;

class SecurityEventLabels
{
    /**
     * @var array<string, array{label: string, tone: string}>
     */
    private const EVENTS = [
        'honeypot_triggered' => [
            'label' => 'تله هانی‌پات',
            'tone' => 'danger',
        ],
        'auth_rate_limit_exceeded' => [
            'label' => 'محدودیت نرخ ورود',
            'tone' => 'warning',
        ],
        'payment_retry_ceiling_reached' => [
            'label' => 'سقف تلاش پرداخت',
            'tone' => 'neutral',
        ],
        'consultation_duplicate_blocked' => [
            'label' => 'درخواست مشاوره تکراری',
            'tone' => 'neutral',
        ],
        'support_open_ticket_cap_reached' => [
            'label' => 'سقف تیکت باز',
            'tone' => 'neutral',
        ],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const META_KEY_LABELS = [
        'honeypot_triggered' => [],
        'auth_rate_limit_exceeded' => [
            'limiter' => 'محدودکننده',
            'retry_after_seconds' => 'زمان انتظار (ثانیه)',
        ],
        'payment_retry_ceiling_reached' => [
            'order_id' => 'شناسه سفارش',
            'payment_id' => 'شناسه پرداخت',
            'retry_count' => 'تعداد تلاش',
            'max_retries' => 'حداکثر تلاش',
        ],
        'consultation_duplicate_blocked' => [
            'open_consultation_request_id' => 'شناسه درخواست باز',
        ],
        'support_open_ticket_cap_reached' => [
            'open_ticket_count' => 'تعداد تیکت باز',
            'max_open_tickets' => 'حداکثر تیکت باز',
        ],
    ];

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function filterOptions(): array
    {
        return array_map(
            fn (string $event, array $config): array => [
                'value' => $event,
                'label' => $config['label'],
            ],
            array_keys(self::EVENTS),
            self::EVENTS,
        );
    }

    /**
     * @return array{label: string, tone: string}
     */
    public static function forEvent(string $event): array
    {
        return self::EVENTS[$event] ?? [
            'label' => $event,
            'tone' => 'muted',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $meta
     * @return list<array{key: string, label: string, value: string}>
     */
    public static function mapMetaForDisplay(string $event, ?array $meta): array
    {
        if ($meta === null || $meta === []) {
            return [];
        }

        $allowedKeys = self::META_KEY_LABELS[$event] ?? [];
        $items = [];

        foreach ($allowedKeys as $key => $label) {
            if (! array_key_exists($key, $meta)) {
                continue;
            }

            $value = $meta[$key];

            if (is_scalar($value) || $value === null) {
                $items[] = [
                    'key' => $key,
                    'label' => $label,
                    'value' => (string) $value,
                ];
            }
        }

        return $items;
    }
}
