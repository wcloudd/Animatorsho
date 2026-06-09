<?php

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Auth\RegistrationCompletionService;
use App\Services\Sms\SmsSettingsService;
use App\Support\AuthIdentifier;
use App\Support\IranianMobile;
use Database\Seeders\SmsTemplateSeeder;
use Illuminate\Testing\TestResponse;
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

function submitIdentifier(string $identifier, array $query = []): TestResponse
{
    $url = route('login.identifier');

    if ($query !== []) {
        $url .= '?'.http_build_query($query);
    }

    return test()->post($url, [
        'identifier' => $identifier,
    ]);
}

test('login page renders unified identifier entry', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/login'));
});

test('existing mobile identifier sends otp and redirects to mobile verify', function () {
    User::factory()->withMobile('09121234567')->create();

    submitIdentifier('09121234567')
        ->assertRedirect(route('auth.mobile.verify'))
        ->assertSessionHas('status', 'otp-sent')
        ->assertSessionHas('mobile_otp.mobile', '09121234567');

    $this->assertGuest();
    expect(OtpCode::query()->where('mobile', '09121234567')->count())->toBe(1);
});

test('existing mobile identifier is normalized before otp send', function () {
    User::factory()->withMobile('09121234567')->create();

    submitIdentifier('+98 912 123 4567')
        ->assertRedirect(route('auth.mobile.verify'))
        ->assertSessionHas('mobile_otp.mobile', '09121234567');
});

test('new mobile identifier stores pending auth mobile and redirects to registration details', function () {
    submitIdentifier('09129876543')
        ->assertRedirect(route('register'))
        ->assertSessionHas(RegistrationCompletionService::SESSION_AUTH_PENDING_MOBILE_KEY, '09129876543');

    $this->assertGuest();
    expect(OtpCode::query()->where('mobile', '09129876543')->count())->toBe(0);
    expect(User::query()->count())->toBe(0);
});

test('registration page redirects to login without pending mobile', function () {
    $this->get(route('register'))
        ->assertRedirect(route('login'));
});

test('registration page renders when pending auth mobile exists', function () {
    $this->withSession([
        RegistrationCompletionService::SESSION_AUTH_PENDING_MOBILE_KEY => '09129876543',
    ])
        ->get(route('register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/register')
            ->where('pendingMobile', '09129876543')
            ->where('mobileLocked', true)
        );
});

test('registration details submit uses pending auth mobile without creating user before otp', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    submitIdentifier('09129876543');

    $this->post(route('register.store'), [
        'name' => 'New User',
        'username' => 'new_user',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertRedirect(route('register.verify'))
        ->assertSessionHas('status', 'otp-sent');

    $this->assertGuest();
    expect(User::query()->count())->toBe(0)
        ->and(session('registration.pending.mobile'))->toBe('09129876543');
});

test('existing email identifier redirects to password login with prefilled email', function () {
    User::factory()->create([
        'email' => 'legacy-user@example.com',
    ]);

    submitIdentifier('legacy-user@example.com')
        ->assertRedirect(route('login.email', ['email' => 'legacy-user@example.com']));

    $this->get(route('login.email', ['email' => 'legacy-user@example.com']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('prefilledEmail', 'legacy-user@example.com')
        );
});

test('unknown email identifier shows signup with mobile validation message', function () {
    submitIdentifier('unknown@example.com')
        ->assertSessionHasErrors([
            'identifier' => AuthIdentifier::UNKNOWN_EMAIL_MESSAGE,
        ]);

    $this->assertGuest();
});

test('invalid identifier is rejected', function () {
    submitIdentifier('not-valid')
        ->assertSessionHasErrors('identifier');
});

test('wrong length mobile identifier shows helpful validation message', function () {
    submitIdentifier('091234567890')
        ->assertSessionHasErrors([
            'identifier' => IranianMobile::INVALID_FORMAT_MESSAGE,
        ]);
});

test('existing mobile identifier flow allows password login with same mobile', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    submitIdentifier('09121234567')
        ->assertRedirect(route('auth.mobile.verify'))
        ->assertSessionHas('mobile_otp.mobile', '09121234567');

    $this->get(route('login.password'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/login-password')
            ->where('maskedMobile', '0912***4567')
        );

    $this->post(route('login.store'), [
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('identifier redirect query is preserved through registration flow', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $target = route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'cash',
    ], absolute: false);

    $this->get(route('login', ['redirect' => $target]));

    submitIdentifier('09125556677');

    $this->post(route('register.store'), [
        'name' => 'Checkout User',
        'username' => 'checkout_user',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('register.verify'));

    $code = OtpTestHelper::extractCodeFromLastSms('09125556677');
    expect($code)->not->toBeNull();

    $response = $this->post(route('register.verify.store'), [
        'code' => $code,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect($target);
});

test('identifier redirect query is preserved through existing mobile otp flow', function () {
    $user = User::factory()->withMobile('09124445566')->create();
    $target = route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'cash',
    ], absolute: false);

    $this->get(route('login', ['redirect' => $target]));

    submitIdentifier('09124445566')
        ->assertRedirect(route('auth.mobile.verify'));

    $code = OtpTestHelper::extractCodeFromLastSms('09124445566');
    expect($code)->not->toBeNull();

    $response = $this->post(route('auth.mobile.verify.store'), [
        'code' => $code,
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect($target);
});

test('identifier submission is rate limited', function () {
    foreach (range(1, 5) as $attempt) {
        submitIdentifier('rate-limit@example.com')
            ->assertSessionHasErrors('identifier');
    }

    submitIdentifier('rate-limit@example.com')
        ->assertStatus(429);
});
