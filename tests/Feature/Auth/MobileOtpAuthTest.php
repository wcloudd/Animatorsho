<?php

use App\Enums\SmsMessageStatus;
use App\Models\OtpCode;
use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Auth\MobileOtpAuthService;
use App\Services\Sms\SmsSettingsService;
use App\Support\IranianMobile;
use Database\Seeders\SmsTemplateSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\Support\OtpTestHelper;

beforeEach(function () {
    prepareAuthPageTests();
    $this->seed(SmsTemplateSeeder::class);
    config(['sms.driver' => 'fake']);
});

function enableSmsForOtpTests(): void
{
    app(SmsSettingsService::class)->update([
        'enabled' => true,
        'admin_notifications_enabled' => false,
        'admin_mobile' => null,
    ]);
}

function sendOtp(string $mobile = '09121234567'): TestResponse
{
    return test()->post(route('auth.mobile.send-code'), [
        'mobile' => $mobile,
    ]);
}

function createExistingUserForOtp(string $mobile = '09121234567'): User
{
    return User::factory()->withMobile($mobile)->create([
        'email' => null,
        'password' => null,
    ]);
}

test('mobile auth entry page renders', function () {
    $this->get(route('auth.mobile.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('auth/mobile'));
});

test('valid mobile sends otp and creates otp_codes row for existing user', function () {
    enableSmsForOtpTests();

    createExistingUserForOtp('09121234567');

    $response = sendOtp('09121234567');

    $response->assertRedirect(route('auth.mobile.verify'));

    expect(OtpCode::query()->where('mobile', '09121234567')->count())->toBe(1)
        ->and(session('mobile_otp.mobile'))->toBe('09121234567');
});

test('invalid mobile is rejected', function () {
    sendOtp('12345')
        ->assertSessionHasErrors('mobile');

    expect(OtpCode::query()->count())->toBe(0);
});

test('twelve digit mobile starting with 09 shows too many digits message', function () {
    sendOtp('091234567891')
        ->assertSessionHasErrors([
            'mobile' => IranianMobile::TOO_MANY_DIGITS_MESSAGE,
        ]);

    expect(OtpCode::query()->count())->toBe(0);
});

test('unknown mobile is rejected and guided to registration', function () {
    enableSmsForOtpTests();

    $initialCount = User::query()->count();

    sendOtp('09129998877')
        ->assertSessionHasErrors([
            'mobile' => MobileOtpAuthService::NO_ACCOUNT_MESSAGE,
        ])
        ->assertRedirect();

    expect(OtpCode::query()->where('mobile', '09129998877')->count())->toBe(0)
        ->and(User::query()->count())->toBe($initialCount)
        ->and(session('mobile_otp.mobile'))->toBeNull();
});

test('unknown mobile does not create a user when otp login is attempted', function () {
    enableSmsForOtpTests();

    $initialCount = User::query()->count();

    sendOtp('09128887766');

    expect(User::query()->count())->toBe($initialCount);
});

test('otp code is hashed and plain code is not stored', function () {
    enableSmsForOtpTests();

    createExistingUserForOtp('09121234567');

    sendOtp('09121234567');

    $otpCode = OtpCode::query()->where('mobile', '09121234567')->first();

    expect($otpCode)->not->toBeNull()
        ->and($otpCode->code_hash)->not->toMatch('/^\d{6}$/')
        ->and(Hash::isHashed($otpCode->code_hash))->toBeTrue();
});

test('existing user can login via otp without creating a new account', function () {
    enableSmsForOtpTests();

    $initialCount = User::query()->count();

    User::factory()->withMobile('09129876543')->create([
        'email' => null,
        'password' => null,
    ]);

    sendOtp('09129876543');

    $code = OtpTestHelper::extractCodeFromLastSms('09129876543');

    expect($code)->not->toBeNull();

    $response = $this->post(route('auth.mobile.verify.store'), [
        'code' => $code,
    ]);

    $this->assertAuthenticated();

    $user = User::query()->where('mobile', '09129876543')->first();

    expect($user)->not->toBeNull()
        ->and(User::query()->count())->toBe($initialCount + 1)
        ->and($user->mobile_verified_at)->not->toBeNull();

    $response->assertRedirect(route('home', absolute: false));
});

test('legacy user without verified mobile gets mobile_verified_at backfilled on otp login', function () {
    enableSmsForOtpTests();

    $user = User::factory()->withUnverifiedMobile('09127776655')->create([
        'email' => 'legacy@example.com',
        'password' => 'password',
    ]);

    expect($user->mobile_verified_at)->toBeNull();

    sendOtp('09127776655');

    $code = OtpTestHelper::extractCodeFromLastSms('09127776655');

    $this->post(route('auth.mobile.verify.store'), [
        'code' => $code,
    ]);

    $this->assertAuthenticatedAs($user);

    expect($user->fresh()->mobile_verified_at)->not->toBeNull();
});

test('existing user with verified mobile logs in via otp', function () {
    enableSmsForOtpTests();

    $user = User::factory()->withMobile('09121112233')->create([
        'email' => 'existing@example.com',
        'password' => 'password',
    ]);

    sendOtp('09121112233');

    $code = OtpTestHelper::extractCodeFromLastSms('09121112233');

    $this->post(route('auth.mobile.verify.store'), [
        'code' => $code,
    ]);

    $this->assertAuthenticatedAs($user);
});

test('expired code is rejected', function () {
    OtpCode::factory()->forMobile('09121234567')->withPlainCode('123456')->expired()->create();

    session(['mobile_otp.mobile' => '09121234567']);

    $this->post(route('auth.mobile.verify.store'), [
        'code' => '123456',
    ])->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('consumed code is rejected', function () {
    OtpCode::factory()->forMobile('09121234567')->withPlainCode('123456')->consumed()->create();

    session(['mobile_otp.mobile' => '09121234567']);

    $this->post(route('auth.mobile.verify.store'), [
        'code' => '123456',
    ])->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('wrong code increments attempts and is rejected', function () {
    $otpCode = OtpCode::factory()->forMobile('09121234567')->withPlainCode('123456')->create();

    session(['mobile_otp.mobile' => '09121234567']);

    $this->post(route('auth.mobile.verify.store'), [
        'code' => '654321',
    ])->assertSessionHasErrors('code');

    expect($otpCode->fresh()->attempts)->toBe(1);
    $this->assertGuest();
});

test('too many attempts rejected', function () {
    OtpCode::factory()->forMobile('09121234567')->withPlainCode('123456')->maxAttempts()->create();

    session(['mobile_otp.mobile' => '09121234567']);

    $this->post(route('auth.mobile.verify.store'), [
        'code' => '123456',
    ])->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('new send invalidates previous active code', function () {
    enableSmsForOtpTests();

    createExistingUserForOtp('09121234567');

    sendOtp('09121234567');
    $firstCode = OtpTestHelper::extractCodeFromLastSms('09121234567');

    $this->travel(121)->seconds();

    sendOtp('09121234567');

    session(['mobile_otp.mobile' => '09121234567']);

    $this->post(route('auth.mobile.verify.store'), [
        'code' => $firstCode,
    ])->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('existing user send-code redirects to verify page', function () {
    enableSmsForOtpTests();

    User::factory()->withMobile('09123334455')->create();

    sendOtp('09123334455')->assertRedirect(route('auth.mobile.verify'));
});

test('unknown mobile send-code does not redirect to verify page', function () {
    enableSmsForOtpTests();

    sendOtp('09124445566')
        ->assertSessionHasErrors('mobile')
        ->assertRedirect();
});

test('sms log is created when sms is enabled', function () {
    enableSmsForOtpTests();

    createExistingUserForOtp('09121234567');

    sendOtp('09121234567');

    $message = SmsMessage::query()->where('mobile', '09121234567')->first();

    expect($message)->not->toBeNull()
        ->and($message->type)->toBe('otp_login')
        ->and($message->status)->toBe(SmsMessageStatus::Sent);
});

test('sms skipped safely when globally disabled', function () {
    Setting::query()->create([
        'group' => 'sms',
        'key' => 'enabled',
        'value' => '0',
    ]);

    createExistingUserForOtp('09121234567');

    sendOtp('09121234567')->assertRedirect(route('auth.mobile.verify'));

    $message = SmsMessage::query()->where('mobile', '09121234567')->first();

    expect($message)->not->toBeNull()
        ->and($message->status)->toBe(SmsMessageStatus::Skipped);
});

test('otp resend is blocked before cooldown ends', function () {
    enableSmsForOtpTests();

    createExistingUserForOtp('09121234567');

    sendOtp('09121234567');

    $this->post(route('auth.mobile.resend-code'))
        ->assertSessionHasErrors('mobile');
});

test('send-code route is rate limited', function () {
    enableSmsForOtpTests();
    config(['otp.resend_cooldown_seconds' => 0]);

    createExistingUserForOtp('09121234567');

    sendOtp('09121234567')->assertRedirect(route('auth.mobile.verify'));
    sendOtp('09121234567')->assertRedirect(route('auth.mobile.verify'));
    sendOtp('09121234567')->assertRedirect(route('auth.mobile.verify'));
    sendOtp('09121234567')->assertTooManyRequests();
});

test('verify route is rate limited', function () {
    config(['otp.resend_cooldown_seconds' => 0]);
    enableSmsForOtpTests();

    createExistingUserForOtp('09121234567');

    sendOtp('09121234567');

    $code = OtpTestHelper::extractCodeFromLastSms('09121234567');

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('auth.mobile.verify.store'), [
            'code' => '000000',
        ]);
    }

    $this->post(route('auth.mobile.verify.store'), [
        'code' => $code,
    ])->assertTooManyRequests();
});

test('mobile auth preserves intended redirect after verify', function () {
    enableSmsForOtpTests();

    User::factory()->withMobile('09125556677')->create();

    $target = route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'cash',
    ], absolute: false);

    $this->get(route('auth.mobile.create', ['redirect' => $target]));

    sendOtp('09125556677');

    $code = OtpTestHelper::extractCodeFromLastSms('09125556677');

    $this->post(route('auth.mobile.verify.store'), [
        'code' => $code,
    ])->assertRedirect($target);
});

test('otp code is not exposed in inertia props', function () {
    enableSmsForOtpTests();

    createExistingUserForOtp('09121234567');

    sendOtp('09121234567');

    $this->get(route('auth.mobile.verify'))
        ->assertSuccessful()
        ->assertInertia(function ($page) {
            $props = json_encode($page->toArray());

            expect($props)->not->toMatch('/"code"\s*:\s*"\d{6}"/');

            $page->has('maskedMobile')
                ->missing('code')
                ->missing('otpCode');
        });
});

test('email password auth still works for users with credentials', function () {
    $user = User::factory()->create([
        'email' => 'password-user@example.com',
    ]);

    $this->post(route('login.email.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('email password login fails for otp only users without email', function () {
    User::factory()->otpOnly('09126667788')->create();

    $this->post(route('login.email.store'), [
        'email' => 'missing@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('verify page redirects to mobile entry when session mobile is missing', function () {
    $this->get(route('auth.mobile.verify'))
        ->assertRedirect(route('auth.mobile.create'));
});
