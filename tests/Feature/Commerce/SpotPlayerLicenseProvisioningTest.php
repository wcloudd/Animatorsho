<?php

use App\Enums\OrderStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Services\SpotPlayerLicenseProvisioningService;
use Database\Seeders\AnimatorshoCourseSeeder;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);
});

test('paid order without user does not create license', function () {
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->guest()
        ->forPackage($package)
        ->paid()
        ->create();

    $license = app(SpotPlayerLicenseProvisioningService::class)->provisionForPaidOrder($order);

    expect($license)->toBeNull()
        ->and(SpotPlayerLicense::query()->count())->toBe(0);
});

test('pending order does not create license', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Pending]);

    $license = app(SpotPlayerLicenseProvisioningService::class)->provisionForPaidOrder($order);

    expect($license)->toBeNull()
        ->and(SpotPlayerLicense::query()->count())->toBe(0);
});

test('provision for paid order is idempotent by order id', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    $service = app(SpotPlayerLicenseProvisioningService::class);

    $first = $service->provisionForPaidOrder($order);
    $second = $service->provisionForPaidOrder($order);

    expect($first)->not->toBeNull()
        ->and($second?->id)->toBe($first->id)
        ->and(SpotPlayerLicense::query()->count())->toBe(1)
        ->and($first->status)->toBe(SpotPlayerLicenseStatus::Pending)
        ->and($first->license_key)->toBeNull();
});
