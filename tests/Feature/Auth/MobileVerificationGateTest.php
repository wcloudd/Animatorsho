<?php

use App\Enums\SupportTicketCategory;
use App\Http\Middleware\EnsureUserHasVerifiedMobile;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Sms\SmsSettingsService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Database\Seeders\SmsTemplateSeeder;
use Laravel\Fortify\Features;
use Tests\Support\OtpTestHelper;

beforeEach(function () {
    prepareAuthPageTests();
    $this->seed(SmsTemplateSeeder::class);
    config(['sms.driver' => 'fake']);

    app(SmsSettingsService::class)->update([
        'enabled' => true,
        'admin_notifications_enabled' => false,
        'admin_mobile' => null,
    ]);
});

function enableSmsForMobileVerificationTests(): void
{
    app(SmsSettingsService::class)->update([
        'enabled' => true,
        'admin_notifications_enabled' => false,
        'admin_mobile' => null,
    ]);
}

test('unverified user cannot create checkout order and is redirected to mobile verification', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->withUnverifiedMobile('09121234567')->create();

    $response = $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'cash',
        ...validCheckoutCustomer(),
    ], [
        'referer' => url('/checkout/confirm?package=full&payment=cash'),
    ]);

    $response
        ->assertRedirect(route('profile.mobile.create'))
        ->assertSessionHas('status', 'mobile-verification-required')
        ->assertSessionHas('url.intended', '/checkout/confirm?package=full&payment=cash');

    expect(Order::query()->count())->toBe(0);
});

test('verified mobile user can create checkout order', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)->post(route('checkout.orders.store'), [
        'package' => 'full',
        'payment' => 'installment',
        ...validCheckoutCustomer(),
        'installment_term' => 'one_month',
    ])->assertRedirect();

    expect(Order::query()->count())->toBe(1);
});

test('user without mobile cannot create support ticket and is redirected to mobile verification', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('support.tickets.store'), [
        'subject' => 'مشکل پرداخت',
        'category' => SupportTicketCategory::Payment->value,
        'message' => 'سلام، پرداخت من تایید نشده است.',
    ], [
        'referer' => url('/support'),
    ]);

    $response
        ->assertRedirect(route('profile.mobile.create'))
        ->assertSessionHas('status', 'mobile-verification-required')
        ->assertSessionHas('url.intended', '/support');

    expect(SupportTicket::query()->count())->toBe(0);
});

test('verified mobile user can create support ticket', function () {
    $user = User::factory()->withMobile()->create(['name' => 'کاربر تست']);

    $this->actingAs($user)->post(route('support.tickets.store'), [
        'subject' => 'مشکل پرداخت',
        'category' => SupportTicketCategory::Payment->value,
        'message' => 'سلام، پرداخت من تایید نشده است.',
    ])->assertRedirect();

    expect(SupportTicket::query()->count())->toBe(1);
});

test('unverified user cannot reply to support ticket and is redirected to mobile verification', function () {
    $user = User::factory()->withUnverifiedMobile()->create();
    $ticket = SupportTicket::factory()->forUser($user)->open()->create();

    $response = $this->actingAs($user)->post(route('support.tickets.messages.store', $ticket), [
        'body' => 'پیام جدید',
    ], [
        'referer' => url('/support/tickets/'.$ticket->id),
    ]);

    $response
        ->assertRedirect(route('profile.mobile.create'))
        ->assertSessionHas('status', 'mobile-verification-required');
});

test('verified user can complete mobile verification and return to intended page', function () {
    enableSmsForMobileVerificationTests();

    $user = User::factory()->withUnverifiedMobile('09124445566')->create();

    session(['url.intended' => '/support']);

    $this->actingAs($user)->post(route('profile.mobile.send-existing-code'))
        ->assertRedirect(route('profile.mobile.verify'));

    $code = OtpTestHelper::extractCodeFromLastSms('09124445566');

    expect($code)->not->toBeNull();

    $this->actingAs($user)->post(route('profile.mobile.verify.store'), [
        'code' => $code,
    ])->assertRedirect('/support');

    expect($user->fresh())
        ->mobile->toBe('09124445566')
        ->mobile_verified_at->not->toBeNull();
});

test('profile mobile with existing unverified mobile sends otp to that mobile', function () {
    enableSmsForMobileVerificationTests();

    $user = User::factory()->withUnverifiedMobile('09125556677')->create();

    $this->actingAs($user)->post(route('profile.mobile.send-existing-code'))
        ->assertRedirect(route('profile.mobile.verify'));

    expect(session('mobile_verification.mobile'))->toBe('09125556677')
        ->and(OtpTestHelper::extractCodeFromLastSms('09125556677'))->not->toBeNull();
});

test('profile mobile page shows existing mobile for verification', function () {
    $user = User::factory()->withUnverifiedMobile('09123334455')->create();

    $this->actingAs($user)
        ->get(route('profile.mobile.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('profile/mobile')
            ->where('existingMobile', '09123334455')
            ->where('maskedExistingMobile', '0912***4455'));
});

test('profile mobile page without existing mobile allows entering a new number', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.mobile.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('profile/mobile')
            ->where('existingMobile', null));
});

test('profile mobile verification page shows required message', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['status' => 'mobile-verification-required'])
        ->get(route('profile.mobile.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('profile/mobile')
            ->where('status', 'mobile-verification-required'));
});

test('verified mobile user visiting profile mobile page is redirected away', function () {
    $user = User::factory()->withMobile()->create();

    $this->actingAs($user)
        ->get(route('profile.mobile.create'))
        ->assertRedirect(route('profile', absolute: false));
});

test('mobile verification otp is not exposed in inertia props', function () {
    enableSmsForMobileVerificationTests();

    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.mobile.send-code'), [
        'mobile' => '09123334455',
    ]);

    $this->actingAs($user)->get(route('profile.mobile.verify'))
        ->assertSuccessful()
        ->assertInertia(function ($page) {
            $props = json_encode($page->toArray());

            expect($props)->not->toMatch('/"code"\s*:\s*"\d{6}"/');

            $page->has('maskedMobile')
                ->missing('code')
                ->missing('otpCode');
        });
});

test('email registration without mobile fails validation', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'gate_test_user',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('mobile');

    $this->assertGuest();
});

test('email registration with invalid mobile fails validation', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'gate_test_user',
        'email' => 'test@example.com',
        'mobile' => '12345',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('mobile');

    $this->assertGuest();
});

test('registration with valid mobile requires otp before account is created', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'gate_test_user',
        'email' => 'test@example.com',
        'mobile' => '+989121234567',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('register.verify'));

    $this->assertGuest();
    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});

test('middleware required message constant matches product copy', function () {
    expect(EnsureUserHasVerifiedMobile::REQUIRED_MESSAGE)
        ->toBe('برای ادامه، ابتدا شماره موبایل خود را ثبت و تأیید کنید.');
});

test('user cannot change mobile to a different number via profile mobile send code', function () {
    enableSmsForMobileVerificationTests();

    $user = User::factory()->withUnverifiedMobile('09125556677')->create();

    $this->actingAs($user)
        ->post(route('profile.mobile.send-code'), [
            'mobile' => '09128889900',
        ])
        ->assertSessionHasErrors('mobile');

    expect($user->fresh()->mobile)->toBe('09125556677');
});

test('verified user cannot change mobile via profile mobile send code', function () {
    enableSmsForMobileVerificationTests();

    $user = User::factory()->withMobile('09125556677')->create();

    $this->actingAs($user)
        ->post(route('profile.mobile.send-code'), [
            'mobile' => '09128889900',
        ])
        ->assertSessionHasErrors('mobile');

    expect($user->fresh()->mobile)->toBe('09125556677');
});
