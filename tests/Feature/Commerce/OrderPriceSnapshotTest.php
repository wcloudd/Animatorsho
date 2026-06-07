<?php

use App\Models\CoursePackage;
use App\Models\Order;

test('order amounts snapshot package price and stay unchanged when catalog price updates', function () {
    $package = CoursePackage::factory()->create([
        'price_toman' => 1_500_000,
    ]);

    $order = Order::factory()->forPackage($package)->create();
    $order->snapshotAmountsFromPackage();
    $order->save();

    expect($order->amount_toman)->toBe(1_500_000)
        ->and($order->final_amount_toman)->toBe(1_500_000);

    $package->update(['price_toman' => 9_999_999]);

    $order->refresh();

    expect($order->amount_toman)->toBe(1_500_000)
        ->and($order->final_amount_toman)->toBe(1_500_000);
});

test('order factory snapshots package price into amount fields', function () {
    $package = CoursePackage::factory()->create([
        'price_toman' => 5_500_000,
    ]);

    $order = Order::factory()->forPackage($package)->create();

    expect($order->amount_toman)->toBe(5_500_000)
        ->and($order->final_amount_toman)->toBe(5_500_000);
});
