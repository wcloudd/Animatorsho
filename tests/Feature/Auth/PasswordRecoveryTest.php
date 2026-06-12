<?php

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Sms\SmsSettingsService;
use Database\Seeders\SmsTemplateSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Features;
use Tests\Support\OtpTestHelper;

beforeEach(function () {
    prepareAuthPageTests();
    $this->skipUnlessFortifyHas(Features::resetPasswords());
    $this->seed(SmsTemplateSeeder::class);
    config(['sms.driver' => 'fake']);
});

function enableSmsForPasswordRecoveryTests(): void
{
    app(SmsSettingsService::class)->update([
        'enabled' => true,
        'admin_notifications_enabled' => false,
        'admin_mobile' => null,
    ]);
}

function disableSmsForPasswordRecoveryTests(): void
{
    app(SmsSettingsService::class)->update([
        'enabled' => false,
        'admin_notifications_enabled' => false,
        'admin_mobile' => null,
    ]);
}

function sendPasswordResetOtp(string $mobile = '09121234567'): TestResponse
{
    return test()->post(route('password.mobile.send-code'), [
        'mobile' => $mobile,
    ]);
}

function completeMobilePasswordReset(string $mobile, string $newPassword = 'new-secure-password'): void
{
    $code = OtpTestHelper::extractCodeFromLastSms($mobile);
    expect($code)->not->toBeNull();

    test()->post(route('password.mobile.verify.store'), [
        'code' => $code,
    ])->assertRedirect(route('password.mobile.reset'));

    test()->post(route('password.mobile.reset.store'), [
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ])->assertRedirect(route('login'));
}

test('forgot password hub renders with sms availability prop', function () {
    enableSmsForPasswordRecoveryTests();

    $this->get(route('password.request'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('auth/forgot-password')
            ->where('smsAvailable', true)
            ->has('status')
        );
});

test('verified mobile user can reset password via mobile otp', function () {
    enableSmsForPasswordRecoveryTests();

    $user = User::factory()->withMobile('09121234567')->create([
        'password' => Hash::make('old-password'),
    ]);

    sendPasswordResetOtp('09121234567')
        ->assertRedirect(route('password.mobile.verify'))
        ->assertSessionHas('status', 'otp-sent');

    expect(OtpCode::query()
        ->forMobile('09121234567', OtpPurpose::PasswordReset)
        ->active()
        ->exists())->toBeTrue();

    completeMobilePasswordReset('09121234567', 'new-secure-password');

    $user->refresh();
    expect(Hash::check('new-secure-password', $user->password))->toBeTrue();
});

test('mobile and password login works with new password after reset', function () {
    enableSmsForPasswordRecoveryTests();

    User::factory()->withMobile('09121234567')->create([
        'password' => Hash::make('old-password'),
    ]);

    sendPasswordResetOtp('09121234567');
    completeMobilePasswordReset('09121234567', 'brand-new-password');

    $this->post(route('login.store'), [
        'mobile' => '09121234567',
        'password' => 'brand-new-password',
    ])->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();
});

test('unknown mobile does not create user or otp row but redirects to verify', function () {
    enableSmsForPasswordRecoveryTests();

    $initialUserCount = User::count();
    $initialOtpCount = OtpCode::count();

    sendPasswordResetOtp('09129998877')
        ->assertRedirect(route('password.mobile.verify'))
        ->assertSessionHas('status', 'otp-sent');

    expect(User::count())->toBe($initialUserCount)
        ->and(OtpCode::count())->toBe($initialOtpCount);
});

test('unknown mobile verify fails generically', function () {
    enableSmsForPasswordRecoveryTests();

    sendPasswordResetOtp('09129998877');

    $this->from(route('password.mobile.verify'))
        ->post(route('password.mobile.verify.store'), [
            'code' => '123456',
        ])
        ->assertRedirect(route('password.mobile.verify'))
        ->assertSessionHasErrors('code');
});

test('sms unavailable blocks mobile reset and shows fallback copy on hub', function () {
    disableSmsForPasswordRecoveryTests();

    $this->get(route('password.request'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('auth/forgot-password')
            ->where('smsAvailable', false)
        );

    $this->from(route('password.request'))
        ->post(route('password.mobile.send-code'), [
            'mobile' => '09121234567',
        ])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors('mobile');
});

test('user with email can still use fortify email reset', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'recovery@example.com',
    ]);

    $this->post(route('password.email'), [
        'email' => 'recovery@example.com',
    ]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('admin email recovery still works', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create([
        'email' => 'admin@example.com',
    ]);

    $this->post(route('password.email'), [
        'email' => 'admin@example.com',
    ]);

    Notification::assertSentTo($admin, ResetPassword::class);

    Notification::assertSentTo($admin, ResetPassword::class, function ($notification) use ($admin) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $admin->email,
            'password' => 'admin-new-password',
            'password_confirmation' => 'admin-new-password',
        ])->assertRedirect(route('login'));

        return true;
    });

    $admin->refresh();
    expect(Hash::check('admin-new-password', $admin->password))->toBeTrue();

    $this->post(route('login.email.store'), [
        'email' => 'admin@example.com',
        'password' => 'admin-new-password',
    ]);

    $this->assertAuthenticatedAs($admin);

    $this->get(route('admin.dashboard'))
        ->assertOk();
});

test('otp only verified user can set first password via mobile reset', function () {
    enableSmsForPasswordRecoveryTests();

    $user = User::factory()->otpOnly('09123334455')->create();

    sendPasswordResetOtp('09123334455');
    completeMobilePasswordReset('09123334455', 'first-password-set');

    $user->refresh();
    expect($user->hasPassword())->toBeTrue()
        ->and(Hash::check('first-password-set', $user->password))->toBeTrue();

    $this->post(route('login.store'), [
        'mobile' => '09123334455',
        'password' => 'first-password-set',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('legacy email login still works after email reset', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'legacy@example.com',
    ]);

    $this->post(route('password.email'), [
        'email' => 'legacy@example.com',
    ]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'legacy-reset-password',
            'password_confirmation' => 'legacy-reset-password',
        ])->assertRedirect(route('login'));

        return true;
    });

    $this->post(route('login.email.store'), [
        'email' => 'legacy@example.com',
        'password' => 'legacy-reset-password',
    ])->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('password reset verify page does not expose otp in inertia props', function () {
    enableSmsForPasswordRecoveryTests();

    User::factory()->withMobile('09121234567')->create();

    sendPasswordResetOtp('09121234567');

    $this->get(route('password.mobile.verify'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('auth/forgot-password-verify')
            ->has('maskedMobile')
            ->missing('code')
            ->missing('otp')
        );
});

test('mobile reset form page does not expose otp in inertia props', function () {
    enableSmsForPasswordRecoveryTests();

    User::factory()->withMobile('09124445566')->create();

    sendPasswordResetOtp('09124445566');

    $code = OtpTestHelper::extractCodeFromLastSms('09124445566');
    expect($code)->not->toBeNull();

    $this->post(route('password.mobile.verify.store'), [
        'code' => $code,
    ])->assertRedirect(route('password.mobile.reset'));

    $this->get(route('password.mobile.reset'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('auth/reset-password-mobile')
            ->has('passwordRules')
            ->missing('code')
            ->missing('otp')
        );
});

test('mobile password reset submit is rate limited', function () {
    enableSmsForPasswordRecoveryTests();

    User::factory()->withMobile('09121234567')->create();

    sendPasswordResetOtp('09121234567');

    $code = OtpTestHelper::extractCodeFromLastSms('09121234567');
    expect($code)->not->toBeNull();

    $this->post(route('password.mobile.verify.store'), [
        'code' => $code,
    ])->assertRedirect(route('password.mobile.reset'));

    foreach (range(1, 5) as $attempt) {
        $this->post(route('password.mobile.reset.store'), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }

    $this->post(route('password.mobile.reset.store'), [
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertStatus(429);
});

test('user without email gets generic response on email reset request', function () {
    Notification::fake();

    User::factory()->otpOnly('09125556677')->create();

    $this->from(route('password.request'))
        ->post(route('password.email'), [
            'email' => 'unknown@example.com',
        ])
        ->assertSessionHasErrors('email');

    Notification::assertNothingSent();
});
