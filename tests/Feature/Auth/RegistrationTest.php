<?php

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Sms\SmsSettingsService;
use Database\Seeders\SmsTemplateSeeder;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Features;
use Tests\Support\OtpTestHelper;

beforeEach(function () {
    $this->withoutVite();
    $this->skipUnlessFortifyHas(Features::registration());
    $this->seed(SmsTemplateSeeder::class);
    config(['sms.driver' => 'fake']);
    app(SmsSettingsService::class)->update([
        'enabled' => true,
        'admin_notifications_enabled' => false,
        'admin_mobile' => null,
    ]);
});

function startRegistration(array $overrides = []): TestResponse
{
    return test()->post(route('register.store'), array_merge([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'mobile' => '09121234567',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides));
}

function completeRegistration(string $mobile = '09121234567'): TestResponse
{
    $code = OtpTestHelper::extractCodeFromLastSms($mobile);

    expect($code)->not->toBeNull();

    return test()->post(route('register.verify.store'), [
        'code' => $code,
    ]);
}

test('registration screen can be rendered', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/register'));
});

test('post register with valid data does not authenticate immediately', function () {
    startRegistration()
        ->assertRedirect(route('register.verify'))
        ->assertSessionHas('status', 'otp-sent');

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('post register stores pending registration and redirects to register verify', function () {
    startRegistration();

    expect(session('registration.pending'))->toBeArray()
        ->and(session('registration_otp.mobile'))->toBe('09121234567')
        ->and(OtpCode::query()->where('mobile', '09121234567')->count())->toBe(1);

    $this->get(route('register.verify'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('auth/register-verify')
            ->has('maskedMobile')
            ->missing('code')
        );
});

test('wrong otp does not create user', function () {
    startRegistration();

    $this->post(route('register.verify.store'), [
        'code' => '000000',
    ])->assertSessionHasErrors('code');

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('correct otp creates user with mobile verified at and logs them in', function () {
    startRegistration();

    completeRegistration()
        ->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();

    $user = User::query()->where('mobile', '09121234567')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test User')
        ->and($user->mobile_verified_at)->not->toBeNull();
});

test('email is optional during registration', function () {
    startRegistration([
        'email' => null,
    ])->assertRedirect(route('register.verify'));

    completeRegistration();

    $user = User::query()->where('mobile', '09121234567')->first();

    expect($user)->not->toBeNull()
        ->and($user->email)->toBeNull();
});

test('provided email is stored after verification', function () {
    startRegistration([
        'email' => 'stored@example.com',
    ]);

    completeRegistration();

    expect(User::query()->where('email', 'stored@example.com')->exists())->toBeTrue();
});

test('mobile is normalized during registration', function () {
    startRegistration([
        'mobile' => '+989121234567',
    ])->assertRedirect(route('register.verify'));

    completeRegistration('09121234567');

    expect(User::query()->where('mobile', '09121234567')->exists())->toBeTrue();
});

test('duplicate mobile is rejected during registration', function () {
    User::factory()->withMobile('09121234567')->create();

    startRegistration([
        'mobile' => '09121234567',
    ])->assertSessionHasErrors('mobile');

    $this->assertGuest();
});

test('user can change mobile before otp verification', function () {
    startRegistration([
        'mobile' => '09121111111',
    ]);

    $this->post(route('register.change-mobile'), [
        'mobile' => '09122222222',
    ])->assertRedirect(route('register.verify'));

    completeRegistration('09122222222');

    expect(User::query()->where('mobile', '09122222222')->exists())->toBeTrue()
        ->and(User::query()->where('mobile', '09121111111')->exists())->toBeFalse();
});

test('registration without mobile fails validation', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('mobile');

    $this->assertGuest();
});

test('registration with invalid mobile fails validation', function () {
    startRegistration([
        'mobile' => '12345',
    ])->assertSessionHasErrors('mobile');

    $this->assertGuest();
});

test('registration verify page redirects when pending session is missing', function () {
    $this->get(route('register.verify'))
        ->assertRedirect(route('register'));
});

test('registration otp is not exposed in inertia props', function () {
    startRegistration();

    $this->get(route('register.verify'))
        ->assertSuccessful()
        ->assertInertia(function ($page) {
            $props = json_encode($page->toArray());

            expect($props)->not->toMatch('/"code"\s*:\s*"\d{6}"/');

            $page->missing('code')->missing('otpCode');
        });
});
