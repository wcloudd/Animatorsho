<?php

namespace App\Services\Sms;

use App\Enums\SmsMessageType;
use App\Models\Setting;
use App\Support\IranianMobile;

class SmsSettingsService
{
    public function __construct(
        private readonly SmsTemplateService $templates,
    ) {}

    public function isEnabled(): bool
    {
        return $this->booleanSetting(Setting::KEY_ENABLED, (bool) config('sms.defaults.enabled', false));
    }

    public function isAdminNotificationsEnabled(): bool
    {
        return $this->booleanSetting(
            Setting::KEY_ADMIN_NOTIFICATIONS_ENABLED,
            (bool) config('sms.defaults.admin_notifications_enabled', true),
        );
    }

    public function adminMobile(): ?string
    {
        $stored = $this->stringSetting(Setting::KEY_ADMIN_MOBILE);
        $fallback = config('sms.defaults.admin_mobile');

        $value = $stored ?? (is_string($fallback) && $fallback !== '' ? $fallback : null);

        return IranianMobile::normalize($value);
    }

    public function currentDriver(): string
    {
        $driver = config('sms.driver');

        return is_string($driver) && $driver !== '' ? $driver : 'log';
    }

    public function isOtpDeliveryAvailable(): bool
    {
        return $this->isEnabled()
            && $this->isDriverConfigured($this->currentDriver())
            && $this->templates->isEnabled(SmsMessageType::OtpLogin);
    }

    /**
     * @param  array{enabled?: bool, admin_notifications_enabled?: bool, admin_mobile?: string|null}  $data
     */
    public function update(array $data): void
    {
        if (array_key_exists('enabled', $data)) {
            $this->writeBoolean(Setting::KEY_ENABLED, (bool) $data['enabled']);
        }

        if (array_key_exists('admin_notifications_enabled', $data)) {
            $this->writeBoolean(Setting::KEY_ADMIN_NOTIFICATIONS_ENABLED, (bool) $data['admin_notifications_enabled']);
        }

        if (array_key_exists('admin_mobile', $data)) {
            $mobile = is_string($data['admin_mobile']) ? trim($data['admin_mobile']) : '';
            $normalized = $mobile !== '' ? IranianMobile::normalize($mobile) : null;

            $this->writeString(Setting::KEY_ADMIN_MOBILE, $normalized);
        }
    }

    /**
     * @return array{
     *     enabled: bool,
     *     adminNotificationsEnabled: bool,
     *     adminMobile: string|null,
     *     driver: string,
     *     driverLabel: string,
     *     driverConfigured: bool
     * }
     */
    public function toAdminArray(): array
    {
        $driver = $this->currentDriver();

        return [
            'enabled' => $this->isEnabled(),
            'adminNotificationsEnabled' => $this->isAdminNotificationsEnabled(),
            'adminMobile' => $this->adminMobile(),
            'driver' => $driver,
            'driverLabel' => $this->driverLabel($driver),
            'driverConfigured' => $this->isDriverConfigured($driver),
        ];
    }

    private function driverLabel(string $driver): string
    {
        return match ($driver) {
            'log' => 'لاگ (توسعه)',
            'fake' => 'فیک (تست)',
            'farazsms' => 'فراز اس‌ام‌اس',
            'kavenegar' => 'کاوه‌نگار',
            'melipayamak' => 'ملی‌پیامک',
            default => $driver,
        };
    }

    private function isDriverConfigured(string $driver): bool
    {
        return match ($driver) {
            'farazsms' => $this->farazSmsConfigured(),
            'log', 'fake' => true,
            default => false,
        };
    }

    private function farazSmsConfigured(): bool
    {
        $config = config('sms.providers.farazsms');

        if (! is_array($config)) {
            return false;
        }

        $apiKey = $config['api_key'] ?? null;
        $sender = $config['sender'] ?? null;

        return is_string($apiKey) && $apiKey !== ''
            && is_string($sender) && $sender !== '';
    }

    private function booleanSetting(string $key, bool $default): bool
    {
        $stored = $this->stringSetting($key);

        if ($stored === null) {
            return $default;
        }

        return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
    }

    private function stringSetting(string $key): ?string
    {
        $value = Setting::query()
            ->where('group', Setting::GROUP_SMS)
            ->where('key', $key)
            ->value('value');

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private function writeBoolean(string $key, bool $value): void
    {
        $this->writeString($key, $value ? 'true' : 'false');
    }

    private function writeString(string $key, ?string $value): void
    {
        Setting::query()->updateOrCreate(
            [
                'group' => Setting::GROUP_SMS,
                'key' => $key,
            ],
            [
                'value' => $value,
            ],
        );
    }
}
