<?php

use App\Models\SecurityEvent;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('guest cannot view admin security events page', function () {
    $this->get(route('admin.security-events.index'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot view admin security events page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.security-events.index'))
        ->assertForbidden();
});

test('admin can view admin security events page', function () {
    $admin = User::factory()->admin()->create();

    SecurityEvent::factory()->honeypotTriggered()->create();

    $this->actingAs($admin)
        ->get(route('admin.security-events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/security-events/index')
            ->has('events.data', 1)
            ->has('eventOptions', 5)
            ->has('filters'));
});

test('admin security events are listed latest first', function () {
    $admin = User::factory()->admin()->create();

    SecurityEvent::factory()->honeypotTriggered()->create([
        'occurred_at' => now()->subHour(),
    ]);

    $latest = SecurityEvent::factory()->authRateLimitExceeded()->create([
        'occurred_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.security-events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.data.0.id', $latest->id)
            ->where('events.data.0.event', 'محدودیت نرخ ورود'));
});

test('admin security events page filters by event type', function () {
    $admin = User::factory()->admin()->create();

    SecurityEvent::factory()->honeypotTriggered()->create();
    SecurityEvent::factory()->authRateLimitExceeded()->create();

    $this->actingAs($admin)
        ->get(route('admin.security-events.index', ['event' => 'honeypot_triggered']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.eventValue', 'honeypot_triggered')
            ->where('filters.event', 'honeypot_triggered'));
});

test('admin security events page filters by date range', function () {
    $admin = User::factory()->admin()->create();

    SecurityEvent::factory()->honeypotTriggered()->create([
        'occurred_at' => '2026-01-15 10:00:00',
    ]);

    $recent = SecurityEvent::factory()->authRateLimitExceeded()->create([
        'occurred_at' => '2026-06-10 12:00:00',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.security-events.index', [
            'from' => '2026-06-01',
            'to' => '2026-06-12',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.id', $recent->id)
            ->where('filters.from', '2026-06-01')
            ->where('filters.to', '2026-06-12'));
});

test('admin security events page filters by user id', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    SecurityEvent::factory()->honeypotTriggered()->for($user)->create();
    SecurityEvent::factory()->authRateLimitExceeded()->guest()->create();

    $this->actingAs($admin)
        ->get(route('admin.security-events.index', ['user_id' => $user->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.userId', $user->id)
            ->where('filters.user_id', $user->id));
});

test('admin security events page search matches ip and route', function () {
    $admin = User::factory()->admin()->create();

    $matched = SecurityEvent::factory()->honeypotTriggered()->create([
        'ip' => '203.0.113.55',
        'route' => 'consultation.store',
    ]);

    SecurityEvent::factory()->authRateLimitExceeded()->create([
        'ip' => '10.0.0.1',
        'route' => 'login.store',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.security-events.index', ['q' => '203.0.113']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.id', $matched->id));

    $this->actingAs($admin)
        ->get(route('admin.security-events.index', ['q' => 'consultation']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.id', $matched->id));
});

test('admin security events page paginates results', function () {
    $admin = User::factory()->admin()->create();

    SecurityEvent::factory()->count(21)->honeypotTriggered()->create();

    $this->actingAs($admin)
        ->get(route('admin.security-events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 20)
            ->where('events.total', 21)
            ->where('events.per_page', 20));
});

test('admin security events page exposes persian labels and safe meta only', function () {
    $admin = User::factory()->admin()->create();

    SecurityEvent::factory()->paymentRetryCeilingReached()->create([
        'meta' => [
            'order_id' => 42,
            'payment_id' => 99,
            'retry_count' => 5,
            'max_retries' => 5,
            'authority' => 'A00000000000000000000000000000000000',
            'password' => 'secret',
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.security-events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.data.0.event', 'سقف تلاش پرداخت')
            ->where('events.data.0.metaItems', fn ($items): bool => collect($items)->contains(
                fn (array $item): bool => $item['key'] === 'order_id' && $item['value'] === '42',
            ))
            ->missing('events.data.0.meta')
            ->missing('events.data.0.password')
            ->missing('events.data.0.authority')
            ->missing('events.data.0.mobile'));
});

test('admin security events page shows guest label when user id is null', function () {
    $admin = User::factory()->admin()->create();

    SecurityEvent::factory()->honeypotTriggered()->guest()->create();

    $this->actingAs($admin)
        ->get(route('admin.security-events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.data.0.userLabel', 'مهمان')
            ->where('events.data.0.userId', null));
});
