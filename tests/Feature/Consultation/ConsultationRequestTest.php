<?php

use App\Http\Middleware\EnsureUserHasVerifiedMobile;
use App\Models\ConsultationRequest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function validConsultationPayload(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'علی رضایی',
        'note' => 'می‌خوام بدونم دوره برای من مناسبه یا نه.',
        'level' => 'beginner',
        'interest' => 'full-course',
    ], $overrides);
}

test('guest cannot post consultation and is redirected to login', function () {
    $this->post(route('consultation.store'), validConsultationPayload())
        ->assertRedirect(route('login'));

    expect(ConsultationRequest::query()->count())->toBe(0);
});

test('logged in user without verified mobile cannot post and is redirected to profile mobile', function () {
    $user = User::factory()->withUnverifiedMobile('09121234567')->create();

    $this->actingAs($user)
        ->post(route('consultation.store'), validConsultationPayload(), [
            'referer' => url('/consultation'),
        ])
        ->assertRedirect(route('profile.mobile.create'))
        ->assertSessionHas('status', 'mobile-verification-required')
        ->assertSessionHas('url.intended', '/consultation');

    expect(ConsultationRequest::query()->count())->toBe(0);
});

test('logged in user without any mobile cannot post and is redirected to profile mobile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('consultation.store'), validConsultationPayload(), [
            'referer' => url('/consultation'),
        ])
        ->assertRedirect(route('profile.mobile.create'))
        ->assertSessionHas('status', 'mobile-verification-required');

    expect(ConsultationRequest::query()->count())->toBe(0);
});

test('logged in user with verified mobile can submit consultation request', function () {
    $user = User::factory()->withMobile('09121112222')->create();

    $this->actingAs($user)
        ->post(route('consultation.store'), validConsultationPayload([
            'full_name' => 'کاربر تست',
        ]))
        ->assertRedirect(route('consultation'));

    $request = ConsultationRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->user_id)->toBe($user->id)
        ->and($request->name)->toBe('کاربر تست')
        ->and($request->mobile)->toBe('09121112222');
});

test('consultation request mobile is snapshotted from users mobile', function () {
    $user = User::factory()->withMobile('09123334455')->create([
        'name' => 'سارا احمدی',
    ]);

    $this->actingAs($user)->post(route('consultation.store'), [
        'full_name' => 'سارا احمدی',
    ])->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->first()?->mobile)->toBe('09123334455');
});

test('posted mobile cannot override users mobile', function () {
    $user = User::factory()->withMobile('09124445566')->create();

    $this->actingAs($user)->post(route('consultation.store'), [
        'full_name' => 'کاربر تست',
        'mobile' => '09999999999',
    ])->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->first()?->mobile)->toBe('09124445566');
});

test('consultation page is public', function () {
    $this->get(route('consultation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('consultation/index'));
});

test('consultation page exposes guest auth state for form cta', function () {
    $this->get(route('consultation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('consultation/index')
            ->where('auth.user', null));
});

test('consultation page exposes verified mobile for verified users', function () {
    $user = User::factory()->withMobile('09125556677')->create();

    $this->actingAs($user)
        ->get(route('consultation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('consultation/index')
            ->where('auth.user.mobile', '09125556677')
            ->where('auth.user.mobile_verified_at', fn ($value) => $value !== null));
});

test('consultation page exposes unverified mobile state for legacy users', function () {
    $user = User::factory()->withUnverifiedMobile('09126667788')->create();

    $this->actingAs($user)
        ->get(route('consultation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('consultation/index')
            ->where('auth.user.mobile', '09126667788')
            ->where('auth.user.mobile_verified_at', null));
});

test('consultation submit redirects back to consultation page on success', function () {
    $user = User::factory()->withMobile('09123456789')->create();

    $this->actingAs($user)
        ->post(route('consultation.store'), [
            'full_name' => 'علی رضایی',
        ])
        ->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->count())->toBe(1);
});

test('consultation submit is throttled per authenticated user', function () {
    $user = User::factory()->withMobile('09129998877')->create();

    for ($i = 0; $i < 3; $i++) {
        $this->actingAs($user)
            ->post(route('consultation.store'), [
                'full_name' => 'علی رضایی',
            ])
            ->assertRedirect();
    }

    $this->actingAs($user)
        ->post(route('consultation.store'), [
            'full_name' => 'علی رضایی',
        ])
        ->assertStatus(429);
});

test('consultation submit requires a valid name', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    $this->actingAs($user)
        ->from(route('consultation'))
        ->post(route('consultation.store'), [
            'full_name' => 'ع',
        ])
        ->assertRedirect(route('consultation'))
        ->assertSessionHasErrors('name');

    expect(ConsultationRequest::query()->count())->toBe(0);
});

test('unverified mobile middleware message matches profile verification copy', function () {
    expect(EnsureUserHasVerifiedMobile::REQUIRED_MESSAGE)
        ->toBe('برای ادامه، ابتدا شماره موبایل خود را ثبت و تأیید کنید.');
});
