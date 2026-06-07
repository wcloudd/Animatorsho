<?php

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Services\UserPackagePurchaseGuard;
use App\Services\Zarinpal\ZarinpalRequestResult;
use App\Services\ZarinpalService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);
});

function fullPackageForRecovery(): CoursePackage
{
    return CoursePackage::query()->where('slug', 'full')->firstOrFail();
}

function createRecoverablePendingZarinpalOrder(User $user, ?CoursePackage $package = null): Order
{
    $package ??= fullPackageForRecovery();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::Pending,
            'payment_type' => OrderPaymentType::Cash,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
        'amount_toman' => $order->final_amount_toman,
    ]);

    return $order;
}

function mockRetryZarinpalSuccess(string $authority = 'A00000000000000000000000000000000099'): string
{
    $gatewayUrl = 'https://sandbox.zarinpal.com/pg/StartPay/'.$authority;

    test()->mock(ZarinpalService::class, function (MockInterface $mock) use ($authority, $gatewayUrl): void {
        $mock->shouldReceive('request')
            ->once()
            ->andReturn(ZarinpalRequestResult::success($authority, $gatewayUrl));
    });

    return $gatewayUrl;
}

test('pending zarinpal order shows continue and cancel actions in profile props', function () {
    $user = User::factory()->create();
    $order = createRecoverablePendingZarinpalOrder($user);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('accessItems.0.accessState', 'payment_pending')
            ->where('accessItems.0.orderId', $order->id)
            ->where('accessItems.0.primaryAction.label', 'ادامه پرداخت')
            ->where('accessItems.0.primaryAction.method', 'post')
            ->where('accessItems.0.secondaryAction.label', 'لغو سفارش')
            ->where('accessItems.0.secondaryAction.requiresConfirm', true)
        );
});

test('recoverable failed online order shows retry and cancel actions in profile', function () {
    $user = User::factory()->create();
    $package = fullPackageForRecovery();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::Failed,
            'payment_type' => OrderPaymentType::Cash,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Failed,
        'amount_toman' => $order->final_amount_toman,
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('accessItems.0.accessState', 'payment_failed')
            ->where('accessItems.0.primaryAction.label', 'ادامه پرداخت')
            ->where('accessItems.0.secondaryAction.label', 'لغو سفارش')
        );
});

test('user can retry own pending online order and is redirected to zarinpal', function () {
    $user = User::factory()->create();
    $order = createRecoverablePendingZarinpalOrder($user);
    $gatewayUrl = mockRetryZarinpalSuccess();

    $this->actingAs($user)
        ->post(route('profile.orders.retry-online-payment', $order))
        ->assertRedirect($gatewayUrl);

    expect($user->orders()->count())->toBe(1)
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($order->payments()->first()->meta['authority'])->toBe('A00000000000000000000000000000000099');
});

test('retry uses order snapshot amount not frontend data', function () {
    $user = User::factory()->create();
    $package = fullPackageForRecovery();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::Pending,
            'payment_type' => OrderPaymentType::Cash,
            'final_amount_toman' => 4_200_000,
            'amount_toman' => 4_200_000,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
        'amount_toman' => 4_200_000,
    ]);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->once()
            ->withArgs(function (Payment $payment): bool {
                return $payment->amount_toman === 4_200_000;
            })
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000099',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000099',
            ));
    });

    $this->actingAs($user)
        ->post(route('profile.orders.retry-online-payment', $order))
        ->assertRedirect();
});

test('retry does not create duplicate order', function () {
    $user = User::factory()->create();
    $order = createRecoverablePendingZarinpalOrder($user);
    mockRetryZarinpalSuccess();

    $this->actingAs($user)
        ->post(route('profile.orders.retry-online-payment', $order));

    expect($user->orders()->count())->toBe(1);
});

test('user cannot retry someone elses order', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = createRecoverablePendingZarinpalOrder($owner);

    $this->actingAs($otherUser)
        ->post(route('profile.orders.retry-online-payment', $order))
        ->assertForbidden();
});

test('user cannot retry paid order', function () {
    $user = User::factory()->create();
    $package = fullPackageForRecovery();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    Payment::factory()->forOrder($order)->paid()->create([
        'method' => PaymentMethod::Zarinpal,
    ]);

    $this->actingAs($user)
        ->post(route('profile.orders.retry-online-payment', $order))
        ->assertForbidden();
});

test('user can cancel own unpaid online pending order', function () {
    $user = User::factory()->create();
    $order = createRecoverablePendingZarinpalOrder($user);

    $this->actingAs($user)
        ->post(route('profile.orders.cancel', $order))
        ->assertRedirect(route('profile'));

    $payment = $order->payments()->first();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($payment->meta)->toHaveKey('cancelled_by_user_at')
        ->and($payment->meta['cancelled_by'])->toBe($user->id);
});

test('cancelled order no longer blocks duplicate purchase', function () {
    $user = User::factory()->create();
    $package = fullPackageForRecovery();
    $order = createRecoverablePendingZarinpalOrder($user, $package);

    $this->actingAs($user)
        ->post(route('profile.orders.cancel', $order));

    $guard = app(UserPackagePurchaseGuard::class);

    expect($guard->hasBlockingAccess($user, $package))->toBeFalse();
});

test('user cannot cancel paid order', function () {
    $user = User::factory()->create();
    $package = fullPackageForRecovery();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    Payment::factory()->forOrder($order)->paid()->create([
        'method' => PaymentMethod::Zarinpal,
    ]);

    $this->actingAs($user)
        ->post(route('profile.orders.cancel', $order))
        ->assertForbidden();
});

test('user cannot cancel card to card reviewing order', function () {
    $user = User::factory()->create();
    $package = fullPackageForRecovery();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::ManualReview,
            'payment_type' => OrderPaymentType::CardToCard,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::CardToCard,
        'status' => PaymentStatus::Reviewing,
    ]);

    $this->actingAs($user)
        ->post(route('profile.orders.cancel', $order))
        ->assertForbidden();
});

test('user cannot cancel installment reviewing order', function () {
    $user = User::factory()->create();
    $package = fullPackageForRecovery();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->installment()
        ->create();

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Installment,
        'status' => PaymentStatus::Reviewing,
    ]);

    $this->actingAs($user)
        ->post(route('profile.orders.cancel', $order))
        ->assertForbidden();
});

test('user cannot cancel order with active license', function () {
    $user = User::factory()->create();
    $package = fullPackageForRecovery();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::Pending,
            'payment_type' => OrderPaymentType::Cash,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
    ]);

    SpotPlayerLicense::factory()
        ->forOrder($order)
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
        ]);

    $this->actingAs($user)
        ->post(route('profile.orders.cancel', $order))
        ->assertForbidden();
});

test('user cannot cancel order with pending license', function () {
    $user = User::factory()->create();
    $package = fullPackageForRecovery();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::Pending,
            'payment_type' => OrderPaymentType::Cash,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
    ]);

    SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'status' => SpotPlayerLicenseStatus::Pending,
        ]);

    $this->actingAs($user)
        ->post(route('profile.orders.cancel', $order))
        ->assertForbidden();
});

test('gateway failed order allows new checkout', function () {
    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000000',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000',
            ));
    });

    $user = User::factory()->create();
    $package = fullPackageForRecovery();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create(['status' => OrderStatus::Failed]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Failed,
    ]);

    $guard = app(UserPackagePurchaseGuard::class);

    expect($guard->hasBlockingAccess($user, $package))->toBeFalse();

    $this->actingAs($user)
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'cash',
            ...validCheckoutCustomer(),
        ])
        ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');

    expect($user->orders()->count())->toBe(2);
});

test('retry gateway failure marks order and payment failed and redirects to profile', function () {
    $user = User::factory()->create();
    $order = createRecoverablePendingZarinpalOrder($user);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->once()
            ->andReturn(ZarinpalRequestResult::failure('Could not connect to Zarinpal.'));
    });

    $this->actingAs($user)
        ->post(route('profile.orders.retry-online-payment', $order))
        ->assertRedirect(route('profile'))
        ->assertSessionHas('error');

    $payment = $order->payments()->first();

    expect($order->fresh()->status)->toBe(OrderStatus::Failed)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($payment->meta['gateway_error'])->toBe('Could not connect to Zarinpal.');
});

test('card to card reviewing order does not show retry or cancel actions in profile', function () {
    $user = User::factory()->create();
    $package = fullPackageForRecovery();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::ManualReview,
            'payment_type' => OrderPaymentType::CardToCard,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::CardToCard,
        'status' => PaymentStatus::Reviewing,
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('accessItems.0.accessState', 'payment_reviewing')
            ->where('accessItems.0.primaryAction', null)
            ->where('accessItems.0.secondaryAction', null)
        );
});
