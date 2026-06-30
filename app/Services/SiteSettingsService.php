<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Site-wide operational settings stored in the settings table (group = site).
 *
 * TODO: Encrypted credential management (Zarinpal/FarazSMS/SpotPlayer secrets) can be
 * implemented later as a separate high-security slice with encrypted DB storage,
 * write-only fields, masking, audit trail, and re-authentication.
 */
class SiteSettingsService
{
    public const string PURCHASES_DISABLED_MESSAGE = 'ثبت‌نام دوره موقتاً غیرفعال است. لطفاً بعداً دوباره مراجعه کنید یا با پشتیبانی تماس بگیرید.';

    /** @var array<string, mixed> */
    private array $resolved = [];

    public function arePurchasesEnabled(): bool
    {
        return $this->booleanSetting(
            Setting::KEY_PURCHASES_ENABLED,
            (bool) config('site.purchases_enabled', true),
        );
    }

    public function isMaintenanceModeEnabled(): bool
    {
        return $this->booleanSetting(
            Setting::KEY_MAINTENANCE_MODE_ENABLED,
            false,
        );
    }

    public function isStudentPanelShowGettingStartedSection(): bool
    {
        return $this->booleanSetting(
            Setting::KEY_STUDENT_PANEL_SHOW_GETTING_STARTED,
            false,
        );
    }

    public function maintenanceTitle(): string
    {
        $stored = $this->stringSetting(Setting::KEY_MAINTENANCE_TITLE);
        $fallback = config('site.maintenance.title');

        if (is_string($stored) && trim($stored) !== '') {
            return trim($stored);
        }

        return is_string($fallback) && $fallback !== ''
            ? $fallback
            : 'در حال بروزرسانی هستیم';
    }

    public function maintenanceMessage(): string
    {
        $stored = $this->stringSetting(Setting::KEY_MAINTENANCE_MESSAGE);
        $fallback = config('site.maintenance.message');

        if (is_string($stored) && trim($stored) !== '') {
            return trim($stored);
        }

        return is_string($fallback) && $fallback !== ''
            ? $fallback
            : 'در حال به‌روزرسانی سایت هستیم. لطفاً چند دقیقه دیگر دوباره سر بزنید.';
    }

    /**
     * @param  array{
     *     purchases_enabled?: bool,
     *     maintenance_mode_enabled?: bool,
     *     maintenance_title?: string|null,
     *     maintenance_message?: string|null,
     *     student_panel_show_getting_started_section?: bool
     * }  $data
     */
    public function update(array $data): void
    {
        if (array_key_exists('purchases_enabled', $data)) {
            $this->writeBoolean(Setting::KEY_PURCHASES_ENABLED, (bool) $data['purchases_enabled']);
        }

        if (array_key_exists('maintenance_mode_enabled', $data)) {
            $this->writeBoolean(Setting::KEY_MAINTENANCE_MODE_ENABLED, (bool) $data['maintenance_mode_enabled']);
        }

        if (array_key_exists('maintenance_title', $data)) {
            $this->writeNullableString(Setting::KEY_MAINTENANCE_TITLE, $data['maintenance_title']);
        }

        if (array_key_exists('maintenance_message', $data)) {
            $this->writeNullableString(Setting::KEY_MAINTENANCE_MESSAGE, $data['maintenance_message']);
        }

        if (array_key_exists('student_panel_show_getting_started_section', $data)) {
            $this->writeBoolean(Setting::KEY_STUDENT_PANEL_SHOW_GETTING_STARTED, (bool) $data['student_panel_show_getting_started_section']);
        }

        $this->resolved = [];
    }

    /**
     * @return array{
     *     purchasesEnabled: bool,
     *     maintenanceModeEnabled: bool,
     *     maintenanceTitle: string,
     *     maintenanceMessage: string,
     *     purchasesDisabledMessage: string,
     *     studentPanelShowGettingStartedSection: bool
     * }
     */
    public function toAdminArray(): array
    {
        return [
            'purchasesEnabled' => $this->arePurchasesEnabled(),
            'maintenanceModeEnabled' => $this->isMaintenanceModeEnabled(),
            'maintenanceTitle' => $this->maintenanceTitle(),
            'maintenanceMessage' => $this->maintenanceMessage(),
            'purchasesDisabledMessage' => self::PURCHASES_DISABLED_MESSAGE,
            'studentPanelShowGettingStartedSection' => $this->isStudentPanelShowGettingStartedSection(),
        ];
    }

    private function booleanSetting(string $key, bool $default): bool
    {
        $cacheKey = 'bool:'.$key;

        if (array_key_exists($cacheKey, $this->resolved)) {
            return (bool) $this->resolved[$cacheKey];
        }

        $stored = $this->stringSetting($key);

        if ($stored === null) {
            $this->resolved[$cacheKey] = $default;

            return $default;
        }

        $this->resolved[$cacheKey] = filter_var($stored, FILTER_VALIDATE_BOOLEAN);

        return (bool) $this->resolved[$cacheKey];
    }

    private function stringSetting(string $key): ?string
    {
        $cacheKey = 'str:'.$key;

        if (array_key_exists($cacheKey, $this->resolved)) {
            $value = $this->resolved[$cacheKey];

            return is_string($value) ? $value : null;
        }

        $value = Setting::query()
            ->where('group', Setting::GROUP_SITE)
            ->where('key', $key)
            ->value('value');

        if (! is_string($value) || $value === '') {
            $this->resolved[$cacheKey] = null;

            return null;
        }

        $this->resolved[$cacheKey] = $value;

        return $value;
    }

    private function writeBoolean(string $key, bool $value): void
    {
        $this->writeString($key, $value ? 'true' : 'false');
    }

    private function writeNullableString(string $key, mixed $value): void
    {
        if ($value === null) {
            $this->writeString($key, null);

            return;
        }

        $normalized = is_string($value) ? trim($value) : '';
        $this->writeString($key, $normalized !== '' ? $normalized : null);
    }

    private function writeString(string $key, ?string $value): void
    {
        Setting::query()->updateOrCreate(
            [
                'group' => Setting::GROUP_SITE,
                'key' => $key,
            ],
            [
                'value' => $value,
            ],
        );
    }
}
