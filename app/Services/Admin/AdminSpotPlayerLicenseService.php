<?php

namespace App\Services\Admin;

use App\Enums\SpotPlayerLicenseStatus;
use App\Models\SpotPlayerLicense;
use App\Services\Sms\SmsNotifier;
use Illuminate\Auth\Access\AuthorizationException;

class AdminSpotPlayerLicenseService
{
    public function __construct(
        private readonly SmsNotifier $smsNotifier,
    ) {}

    public function activate(SpotPlayerLicense $license, string $licenseKey): SpotPlayerLicense
    {
        if (! in_array($license->status, [
            SpotPlayerLicenseStatus::Pending,
            SpotPlayerLicenseStatus::Failed,
            SpotPlayerLicenseStatus::Revoked,
        ], true)) {
            throw new AuthorizationException('This license cannot be activated.');
        }

        $license->update([
            'license_key' => $licenseKey,
            'status' => SpotPlayerLicenseStatus::Active,
            'activated_at' => now(),
        ]);

        $license = $license->fresh();

        $this->smsNotifier->notifyLicenseActivated($license);

        return $license;
    }

    public function revoke(SpotPlayerLicense $license): SpotPlayerLicense
    {
        if ($license->status !== SpotPlayerLicenseStatus::Active) {
            throw new AuthorizationException('Only active licenses can be revoked.');
        }

        $license->update([
            'status' => SpotPlayerLicenseStatus::Revoked,
        ]);

        return $license->fresh();
    }
}
