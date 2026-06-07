<?php

namespace App\Services\Admin;

use App\Services\SiteSettingsService;
use App\Support\IntegrationStatusPresenter;

class AdminSiteSettingsService
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly IntegrationStatusPresenter $integrations,
    ) {}

    /**
     * @return array{
     *     settings: array{
     *         purchasesEnabled: bool,
     *         maintenanceModeEnabled: bool,
     *         maintenanceTitle: string,
     *         maintenanceMessage: string,
     *         purchasesDisabledMessage: string
     *     },
     *     integrations: array{
     *         zarinpalConfigured: bool,
     *         farazSmsConfigured: bool,
     *         spotPlayerConfigured: bool
     *     },
     *     cardToCard: array{
     *         configured: bool,
     *         source: string,
     *         cardNumber: string,
     *         cardOwnerName: string
     *     }
     * }
     */
    public function indexForAdmin(): array
    {
        return [
            'settings' => $this->settings->toAdminArray(),
            'integrations' => $this->integrations->toAdminArray(),
            'cardToCard' => $this->integrations->cardToCardToAdminArray(),
        ];
    }

    /**
     * @param  array{
     *     purchases_enabled: bool,
     *     maintenance_mode_enabled: bool,
     *     maintenance_title?: string|null,
     *     maintenance_message?: string|null
     * }  $data
     */
    public function updateSettings(array $data): void
    {
        $this->settings->update($data);
    }
}
