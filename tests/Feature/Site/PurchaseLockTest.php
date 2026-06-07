<?php

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\SiteSettingsService;
use App\Services\Zarinpal\ZarinpalRequestResult;
use App\Services\ZarinpalService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);
    $this->withoutVite();

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000000',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000',
            ));
    });
});

function disablePurchases(): void
{
    app(SiteSettingsService::class)->update(['purchases_enabled' => false]);
}

test('purchase lock blocks new checkout order submission', function () {
    disablePurchases();

    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)
        ->from(route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']))
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'cash',
            ...validCheckoutCustomer(),
        ])
        ->assertRedirect(route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']))
        ->assertSessionHasErrors(['package' => SiteSettingsService::PURCHASES_DISABLED_MESSAGE]);

    expect(Order::query()->count())->toBe(0);
});

test('purchase lock blocks installment checkout submission', function () {
    disablePurchases();

    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)
        ->from(route('checkout.confirm', ['package' => 'full', 'payment' => 'installment']))
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'installment',
            'installment_term' => 'one_month',
            ...validCheckoutCustomer(),
        ])
        ->assertSessionHasErrors(['package']);

    expect(Order::query()->count())->toBe(0);
});

test('checkout confirm page exposes purchase disabled props', function () {
    disablePurchases();

    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)
        ->get(route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/confirm')
            ->where('purchasesDisabled', true)
            ->where('purchasesDisabledMessage', SiteSettingsService::PURCHASES_DISABLED_MESSAGE));
});

test('checkout index page exposes purchase disabled props', function () {
    disablePurchases();

    $this->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/index')
            ->where('purchasesDisabled', true));
});

test('retry online payment still works when purchases are disabled', function () {
    disablePurchases();

    $user = User::factory()->withMobile('09121234567')->create();
    $order = createPurchaseLockRecoverablePendingZarinpalOrder($user);

    $this->actingAs($user)
        ->post(route('profile.orders.retry-online-payment', $order))
        ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000');
});

test('purchases enabled allows new checkout order submission', function () {
    app(SiteSettingsService::class)->update(['purchases_enabled' => true]);

    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'cash',
            ...validCheckoutCustomer(),
        ])
        ->assertRedirect();

    expect(Order::query()->count())->toBe(1);
});

function createPurchaseLockRecoverablePendingZarinpalOrder(User $user): Order
{
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

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
