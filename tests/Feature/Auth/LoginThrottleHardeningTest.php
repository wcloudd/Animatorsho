<?php

use App\Models\User;
use App\Support\AuthThrottleMessage;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    prepareAuthPageTests();
});

/**
 * @return array<string, string>
 */
function throttleHardeningInertiaHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'text/html, application/xhtml+xml',
    ];
}

test('five failed mobile logins trigger throttle with at least twenty minute lockout', function () {
    User::factory()->withMobile('09121234567')->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'mobile' => '09121234567',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('mobile');
    }

    $response = $this->post(route('login.store'), [
        'mobile' => '09121234567',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
    expect((int) $response->headers->get('Retry-After'))->toBeGreaterThanOrEqual(1200);
});

test('password-only login step cannot bypass mobile login throttle', function () {
    User::factory()->withMobile('09121234567')->create();

    foreach (range(1, 5) as $attempt) {
        $this->withSession(['mobile_otp.mobile' => '09121234567'])
            ->post(route('login.store'), [
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('mobile');
    }

    $this->withSession(['mobile_otp.mobile' => '09121234567'])
        ->post(route('login.store'), [
            'password' => 'password',
        ]);

    $this->assertGuest();
});

test('successful login still works after a few failed attempts', function () {
    $user = User::factory()->withMobile('09121234567')->create();

    foreach (range(1, 2) as $attempt) {
        $this->post(route('login.store'), [
            'mobile' => '09121234567',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('mobile');
    }

    $this->post(route('login.store'), [
        'mobile' => '09121234567',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('successful login clears the login throttle counter', function () {
    $user = User::factory()->withMobile('09129876543')->create();

    foreach (range(1, 4) as $attempt) {
        $this->post(route('login.store'), [
            'mobile' => '09129876543',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('mobile');
    }

    $this->post(route('login.store'), [
        'mobile' => '09129876543',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $this->post(route('logout'));

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'mobile' => '09129876543',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('mobile');
    }

    $this->post(route('login.store'), [
        'mobile' => '09129876543',
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

test('inertia throttle response shows persian message with approximate minutes', function () {
    User::factory()->withMobile('09121234567')->create();

    $this->get(route('login'));

    foreach (range(1, 5) as $attempt) {
        $this->withHeaders(throttleHardeningInertiaHeaders())
            ->post(route('login.store'), [
                'mobile' => '09121234567',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('mobile');
    }

    $this->withHeaders(throttleHardeningInertiaHeaders())
        ->from(route('login'))
        ->post(route('login.store'), [
            'mobile' => '09121234567',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('throttle');

    expect(session('errors')->get('throttle')[0])
        ->toStartWith(AuthThrottleMessage::BASE_MESSAGE)
        ->toContain('دقیقه');
});

test('login throttle uses configured max attempts from security config', function () {
    expect((int) config('security.rate_limits.login.max_attempts'))->toBe(5)
        ->and((int) config('security.rate_limits.login.decay_minutes'))->toBe(20);
});

test('login throttle key is cleared after successful authentication', function () {
    $user = User::factory()->withMobile('09121110000')->create();

    $this->post(route('login.store'), [
        'mobile' => '09121110000',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('mobile');

    $this->post(route('login.store'), [
        'mobile' => '09121110000',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);

    expect(RateLimiter::attempts('09121110000|127.0.0.1'))->toBe(0);
});
