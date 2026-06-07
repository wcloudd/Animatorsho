<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SmsMessageStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Support\AdminStatusLabels;

test('failed and rejected admin statuses use danger tone', function () {
    expect(AdminStatusLabels::orderStatusTone(OrderStatus::Failed))->toBe('danger')
        ->and(AdminStatusLabels::paymentStatusTone(PaymentStatus::Failed))->toBe('danger')
        ->and(AdminStatusLabels::licenseStatusTone(SpotPlayerLicenseStatus::Failed))->toBe('danger')
        ->and(AdminStatusLabels::smsStatusTone(SmsMessageStatus::Failed))->toBe('danger')
        ->and(AdminStatusLabels::smsStatusTone(SmsMessageStatus::Skipped))->toBe('danger');
});

test('non-failure admin statuses keep existing profile tones', function () {
    expect(AdminStatusLabels::orderStatusTone(OrderStatus::Paid))->toBe('success')
        ->and(AdminStatusLabels::orderStatusTone(OrderStatus::Cancelled))->toBe('neutral')
        ->and(AdminStatusLabels::paymentStatusTone(PaymentStatus::Reviewing))->toBe('warning')
        ->and(AdminStatusLabels::licenseStatusTone(SpotPlayerLicenseStatus::Active))->toBe('success')
        ->and(AdminStatusLabels::smsStatusTone(SmsMessageStatus::Sent))->toBe('success');
});
