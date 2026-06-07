<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Models\User;

test('course has many packages', function () {
    $course = Course::factory()->published()->create();
    $package = CoursePackage::factory()->for($course)->fullCourse()->create();

    expect($course->packages)->toHaveCount(1)
        ->and($course->packages->first()->is($package))->toBeTrue()
        ->and($package->course->is($course))->toBeTrue();
});

test('order links user package payments and license', function () {
    $user = User::factory()->create();
    $package = CoursePackage::factory()->fullCourse()->create();
    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    $payment = Payment::factory()->forOrder($order)->paid()->create();
    $license = SpotPlayerLicense::factory()->forOrder($order)->active()->create([
        'user_id' => $user->id,
    ]);

    expect($user->orders->contains($order))->toBeTrue()
        ->and($order->coursePackage->is($package))->toBeTrue()
        ->and($order->payments->contains($payment))->toBeTrue()
        ->and($payment->order->is($order))->toBeTrue()
        ->and($order->spotPlayerLicense->is($license))->toBeTrue()
        ->and($user->spotPlayerLicenses->contains($license))->toBeTrue()
        ->and($license->coursePackage->is($package))->toBeTrue();
});

test('order number matches AS date and six uppercase alnum format', function () {
    $orderNumber = Order::generateOrderNumber();

    expect($orderNumber)->toMatch('/^AS-\d{8}-[A-Z0-9]{6}$/');
});

test('payment factory forOrder does not create an extra order', function () {
    $user = User::factory()->create();
    $package = CoursePackage::factory()->fullCourse()->create();
    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create();

    expect(Order::query()->count())->toBe(1);

    $payment = Payment::factory()->forOrder($order)->create();

    expect(Order::query()->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(1)
        ->and($payment->order->is($order))->toBeTrue()
        ->and($payment->amount_toman)->toBe($order->final_amount_toman);
});

test('payment factory for relationship does not create an extra order', function () {
    $order = Order::factory()->create();

    expect(Order::query()->count())->toBe(1);

    $payment = Payment::factory()->for($order)->create();

    expect(Order::query()->count())->toBe(1)
        ->and($payment->order->is($order))->toBeTrue()
        ->and($payment->amount_toman)->toBe($order->final_amount_toman);
});

test('payment factory paid and cardToCard states work with forOrder', function () {
    $order = Order::factory()->create();

    expect(Order::query()->count())->toBe(1);

    $paid = Payment::factory()->forOrder($order)->paid()->create();

    expect($paid->status)->toBe(PaymentStatus::Paid)
        ->and($paid->paid_at)->not->toBeNull()
        ->and($paid->tracking_code)->not->toBeNull()
        ->and(Order::query()->count())->toBe(1);

    $cardOrder = Order::factory()->create();
    $card = Payment::factory()->forOrder($cardOrder)->cardToCard()->create();

    expect($card->method)->toBe(PaymentMethod::CardToCard)
        ->and($card->status)->toBe(PaymentStatus::Reviewing)
        ->and(Order::query()->count())->toBe(2);
});

test('bare payment factory creates exactly one order and one payment', function () {
    expect(Order::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);

    $payment = Payment::factory()->create();

    expect(Order::query()->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(1)
        ->and($payment->order)->not->toBeNull()
        ->and($payment->amount_toman)->toBe($payment->order->final_amount_toman);
});
