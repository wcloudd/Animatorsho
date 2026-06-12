<?php

use App\Models\User;
use App\Support\AuthThrottleMessage;

beforeEach(function () {
    prepareAuthPageTests();
});

/**
 * @return array<string, string>
 */
function inertiaAuthHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'text/html, application/xhtml+xml',
    ];
}

test('inertia auth login returns throttle validation error instead of raw 429 page', function () {
    User::factory()->withMobile('09121234567')->create();

    $this->get(route('login'));

    foreach (range(1, 5) as $attempt) {
        $this->withHeaders(inertiaAuthHeaders())
            ->post(route('login.store'), [
                'mobile' => '09121234567',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('mobile');
    }

    $this->withHeaders(inertiaAuthHeaders())
        ->from(route('login'))
        ->post(route('login.store'), [
            'mobile' => '09121234567',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('throttle');

    expect(session('errors')->get('throttle')[0])
        ->toStartWith(AuthThrottleMessage::BASE_MESSAGE);
});

test('non inertia auth login still returns raw 429 when rate limited', function () {
    User::factory()->withMobile('09121234567')->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'mobile' => '09121234567',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('mobile');
    }

    $this->post(route('login.store'), [
        'mobile' => '09121234567',
        'password' => 'wrong-password',
    ])->assertStatus(429);
});
