<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\Order;
use App\Models\SpotPlayerLicense;

class SpotPlayerLicenseProvisioningService
{
    public function provisionForPaidOrder(Order $order): ?SpotPlayerLicense
    {
        if ($order->status !== OrderStatus::Paid) {
            return null;
        }

        if ($order->user_id === null || $order->course_package_id === null) {
            return null;
        }

        return SpotPlayerLicense::query()->firstOrCreate(
            ['order_id' => $order->id],
            [
                'user_id' => $order->user_id,
                'course_package_id' => $order->course_package_id,
                'license_key' => null,
                'status' => SpotPlayerLicenseStatus::Pending,
            ],
        );
    }
}
