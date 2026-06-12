<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Zarinpal\ZarinpalRequestResult;
use App\Services\ZarinpalService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Mockery\MockInterface;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $this->mock(ZarinpalService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('request')
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000000',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000',
            ));
    });
});

function recoverableOrderForThrottle(User $user): Order
{
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::Pending,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
        'amount_toman' => $order->final_amount_toman,
    ]);

    return $order;
}

test('checkout order creation is throttled per authenticated user', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($user)
            ->post(route('checkout.orders.store'), [
                'package' => 'full',
                'payment' => 'cash',
                ...validCheckoutCustomer(),
            ])->assertRedirect();
    }

    $this->actingAs($user)
        ->post(route('checkout.orders.store'), [
            'package' => 'full',
            'payment' => 'cash',
            ...validCheckoutCustomer(),
        ])
        ->assertStatus(429);
});

test('online payment retry route is throttled per authenticated user', function () {
    $user = User::factory()->create();
    $order = recoverableOrderForThrottle($user);

    foreach (range(1, 3) as $attempt) {
        $this->actingAs($user)
            ->post(route('profile.orders.retry-online-payment', $order))
            ->assertRedirect();
    }

    $this->actingAs($user)
        ->post(route('profile.orders.retry-online-payment', $order))
        ->assertStatus(429);
});

test('order cancel route is throttled per authenticated user', function () {
    $user = User::factory()->create();
    $order = recoverableOrderForThrottle($user);

    $this->actingAs($user)
        ->post(route('profile.orders.cancel', $order))
        ->assertRedirect(route('profile'));

    foreach (range(2, 5) as $attempt) {
        $this->actingAs($user)
            ->post(route('profile.orders.cancel', $order))
            ->assertForbidden();
    }

    $this->actingAs($user)
        ->post(route('profile.orders.cancel', $order))
        ->assertStatus(429);
});

test('zarinpal callback route is not throttled', function () {
    foreach (range(1, 6) as $attempt) {
        $this->get(route('checkout.zarinpal.callback'))
            ->assertRedirect(route('checkout.result'));
    }
});
