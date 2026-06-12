<?php

use App\Models\SecurityEvent;

beforeEach(function () {
    config(['security.logging.database_retention_days' => 90]);
});

test('security prune command deletes only events older than retention window', function () {
    SecurityEvent::factory()->honeypotTriggered()->create([
        'occurred_at' => now()->subDays(91),
    ]);

    SecurityEvent::factory()->authRateLimitExceeded()->create([
        'occurred_at' => now()->subDays(90)->subHour(),
    ]);

    $recent = SecurityEvent::factory()->paymentRetryCeilingReached()->create([
        'occurred_at' => now()->subDays(10),
    ]);

    $this->artisan('security:prune-events')
        ->assertSuccessful()
        ->expectsOutputToContain('Deleted 2 security event(s)');

    expect(SecurityEvent::query()->count())->toBe(1)
        ->and(SecurityEvent::query()->first()?->id)->toBe($recent->id);
});

test('security prune command keeps recent events', function () {
    SecurityEvent::factory()->count(3)->honeypotTriggered()->create([
        'occurred_at' => now()->subDays(5),
    ]);

    $this->artisan('security:prune-events')
        ->assertSuccessful()
        ->expectsOutputToContain('No security events older than 90 days were found.');

    expect(SecurityEvent::query()->count())->toBe(3);
});

test('security prune command dry run does not delete records', function () {
    SecurityEvent::factory()->count(2)->honeypotTriggered()->create([
        'occurred_at' => now()->subDays(120),
    ]);

    $this->artisan('security:prune-events --dry-run')
        ->assertSuccessful()
        ->expectsOutputToContain('Dry run: 2 security event(s) would be deleted');

    expect(SecurityEvent::query()->count())->toBe(2);
});

test('security prune command does not delete when retention days is invalid', function () {
    config(['security.logging.database_retention_days' => 0]);

    SecurityEvent::factory()->honeypotTriggered()->create([
        'occurred_at' => now()->subDays(120),
    ]);

    $this->artisan('security:prune-events')
        ->assertFailed()
        ->expectsOutputToContain('Retention days must be a positive integer');

    expect(SecurityEvent::query()->count())->toBe(1);
});

test('security prune command does not delete when retention days is negative', function () {
    config(['security.logging.database_retention_days' => -10]);

    SecurityEvent::factory()->honeypotTriggered()->create([
        'occurred_at' => now()->subDays(120),
    ]);

    $this->artisan('security:prune-events')
        ->assertFailed();

    expect(SecurityEvent::query()->count())->toBe(1);
});

test('security prune command accepts custom days override', function () {
    SecurityEvent::factory()->honeypotTriggered()->create([
        'occurred_at' => now()->subDays(40),
    ]);

    SecurityEvent::factory()->authRateLimitExceeded()->create([
        'occurred_at' => now()->subDays(10),
    ]);

    $this->artisan('security:prune-events --days=30')
        ->assertSuccessful()
        ->expectsOutputToContain('Deleted 1 security event(s)');

    expect(SecurityEvent::query()->count())->toBe(1);
});

test('security prune command rejects invalid custom days override', function () {
    SecurityEvent::factory()->honeypotTriggered()->create([
        'occurred_at' => now()->subDays(120),
    ]);

    $this->artisan('security:prune-events --days=0')
        ->assertFailed();

    expect(SecurityEvent::query()->count())->toBe(1);
});
