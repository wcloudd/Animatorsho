<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SmsMessageStatus;
use App\Enums\SpotPlayerLicenseStatus;

class AdminStatusLabels
{
    /**
     * @return 'success'|'warning'|'neutral'|'danger'
     */
    public static function orderStatusTone(OrderStatus $status): string
    {
        if ($status === OrderStatus::Failed) {
            return 'danger';
        }

        return ProfileStatusLabels::orderStatusTone($status);
    }

    /**
     * @return 'success'|'warning'|'neutral'|'danger'
     */
    public static function paymentStatusTone(PaymentStatus $status): string
    {
        if ($status === PaymentStatus::Failed) {
            return 'danger';
        }

        return ProfileStatusLabels::paymentStatusTone($status);
    }

    /**
     * @return 'success'|'warning'|'neutral'|'danger'
     */
    public static function licenseStatusTone(SpotPlayerLicenseStatus $status): string
    {
        if ($status === SpotPlayerLicenseStatus::Failed) {
            return 'danger';
        }

        return ProfileStatusLabels::licenseStatusTone($status);
    }

    /**
     * @return 'success'|'warning'|'neutral'|'danger'
     */
    public static function smsStatusTone(SmsMessageStatus $status): string
    {
        return match ($status) {
            SmsMessageStatus::Failed,
            SmsMessageStatus::Skipped => 'danger',
            default => SmsStatusLabels::statusTone($status),
        };
    }
}
