<?php

use App\Models\ConsultationRequest;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Zarinpal\ZarinpalRequestResult;
use App\Services\ZarinpalService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('checkout confirm exposes unverified mobile state for legacy authenticated users', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->withUnverifiedMobile('09126667788')->create();

    $this->actingAs($user)
        ->get(route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/confirm')
            ->where('auth.user.mobile', '09126667788')
            ->where('auth.user.mobile_verified_at', null));
});

test('checkout confirm exposes verified mobile for verified users', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->withMobile('09125556677')->create();

    $this->actingAs($user)
        ->get(route('checkout.confirm', ['package' => 'full', 'payment' => 'cash']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/confirm')
            ->where('auth.user.mobile', '09125556677')
            ->where('auth.user.mobile_verified_at', fn ($value) => $value !== null));
});

test('consultation submit ignores otp fields in payload', function () {
    $user = User::factory()->withMobile('09121112222')->create();

    $this->actingAs($user)->post(route('consultation.store'), [
        'full_name' => 'کاربر تست',
        'code' => '123456',
        'otp' => '123456',
    ])->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->count())->toBe(1);
});

test('support ticket create ignores otp fields in payload', function () {
    $user = User::factory()->withMobile('09123334455')->create();

    $this->actingAs($user)->post(route('support.tickets.store'), [
        'subject' => 'مشکل پرداخت',
        'category' => 'payment',
        'message' => 'سلام، پرداخت من تایید نشده است.',
        'code' => '123456',
        'otp' => '123456',
    ])->assertRedirect();

    expect(SupportTicket::query()->count())->toBe(1);
});

test('checkout order create ignores otp fields in payload for verified users', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->withMobile('09124445566')->create();

    $this->mock(ZarinpalService::class, function ($mock): void {
        $mock->shouldReceive('request')
            ->andReturn(ZarinpalRequestResult::success(
                'A00000000000000000000000000000000000',
                'https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000000',
            ));
    });

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
        'code' => '123456',
        'otp' => '123456',
    ])->assertRedirect();

    expect(Order::query()->count())->toBe(1);
});
