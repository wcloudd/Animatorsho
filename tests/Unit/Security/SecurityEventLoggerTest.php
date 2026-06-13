<?php

use App\Models\SecurityEvent;
use App\Services\Security\SecurityEventLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'security.logging.enabled' => true,
        'security.logging.channel' => 'security',
        'security.logging.user_agent_max_length' => 200,
        'security.logging.database_enabled' => true,
    ]);
});

/**
 * @return object{messages: list<MessageLogged>}
 */
function captureLoggedMessages(): object
{
    $capture = new stdClass;
    $capture->messages = [];

    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($capture): void {
        $capture->messages[] = $event;
    });

    return $capture;
}

test('security event logger writes structured warning payload to security channel', function () {
    $captured = captureLoggedMessages();

    $request = Request::create('/consultation', 'POST', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser',
        'REMOTE_ADDR' => '203.0.113.10',
    ]);
    $route = new Route('POST', '/consultation', fn () => null);
    $route->name('consultation.store');
    $request->setRouteResolver(fn () => $route);

    app(SecurityEventLogger::class)->honeypotTriggered($request);

    expect($captured->messages)->toHaveCount(1)
        ->and($captured->messages[0]->level)->toBe('warning')
        ->and($captured->messages[0]->message)->toBe('honeypot_triggered')
        ->and($captured->messages[0]->context['event'])->toBe('honeypot_triggered')
        ->and($captured->messages[0]->context['route'])->toBe('consultation.store')
        ->and($captured->messages[0]->context['method'])->toBe('POST')
        ->and($captured->messages[0]->context['ip'])->toBe('203.0.113.10')
        ->and($captured->messages[0]->context['user_agent'])->toBe('Mozilla/5.0 Test Browser')
        ->and($captured->messages[0]->context)->toHaveKey('occurred_at');
});

test('security event logger writes nothing when logging is disabled', function () {
    config(['security.logging.enabled' => false]);

    $captured = captureLoggedMessages();

    app(SecurityEventLogger::class)->honeypotTriggered();

    expect($captured->messages)->toBeEmpty();
});

test('security event logger truncates long user agent values', function () {
    config(['security.logging.user_agent_max_length' => 20]);

    $captured = captureLoggedMessages();

    $longUserAgent = str_repeat('A', 100);
    $request = Request::create('/login', 'POST', [], [], [], [
        'HTTP_USER_AGENT' => $longUserAgent,
    ]);

    app(SecurityEventLogger::class)->honeypotTriggered($request);

    expect($captured->messages)->toHaveCount(1)
        ->and($captured->messages[0]->context['user_agent'])->toBe(substr($longUserAgent, 0, 20))
        ->and(strlen($captured->messages[0]->context['user_agent']))->toBe(20);
});

test('security event logger strips forbidden sensitive keys from context', function () {
    $captured = captureLoggedMessages();

    app(SecurityEventLogger::class)->log('test_event', [
        'password' => 'secret-password',
        'code' => '123456',
        'token' => 'reset-token',
        'authority' => 'A00000000000000000000000000000000000',
        'ref_id' => '12345',
        'preferred_contact_window' => 'bot-value',
        'email' => 'user@example.com',
        'mobile' => '09121234567',
        'session_id' => 'abc123',
        'authorization' => 'Bearer token',
        'body' => 'request body',
        'request_body' => 'raw body',
        'order_id' => 99,
    ]);

    $forbiddenKeys = [
        'password',
        'code',
        'token',
        'authority',
        'ref_id',
        'preferred_contact_window',
        'email',
        'mobile',
        'session_id',
        'authorization',
        'body',
        'request_body',
    ];

    expect($captured->messages)->toHaveCount(1)
        ->and($captured->messages[0]->message)->toBe('test_event')
        ->and($captured->messages[0]->context['event'])->toBe('test_event')
        ->and($captured->messages[0]->context['order_id'])->toBe(99);

    foreach ($forbiddenKeys as $key) {
        expect($captured->messages[0]->context)->not->toHaveKey($key);
    }
});

test('auth rate limit exceeded includes limiter throttle type and retry after seconds', function () {
    $captured = captureLoggedMessages();

    $request = Request::create('/login', 'POST', ['mobile' => '09121234567']);
    $route = new Route('POST', '/login', fn () => null);
    $route->name('login.store');
    $request->setRouteResolver(fn () => $route);

    $exception = new TooManyRequestsHttpException(1200, 'Too Many Attempts.');

    app(SecurityEventLogger::class)->authRateLimitExceeded($exception, $request);

    expect($captured->messages)->toHaveCount(1)
        ->and($captured->messages[0]->message)->toBe('auth_rate_limit_exceeded')
        ->and($captured->messages[0]->context['event'])->toBe('auth_rate_limit_exceeded')
        ->and($captured->messages[0]->context['limiter'])->toBe('login')
        ->and($captured->messages[0]->context['throttle_type'])->toBe('login')
        ->and($captured->messages[0]->context['decay_minutes'])->toBe(20)
        ->and($captured->messages[0]->context['retry_after_seconds'])->toBe(1200);
});

test('security event logger persists sanitized row when database logging is enabled', function () {
    $request = Request::create('/consultation', 'POST', [], [], [], [
        'REMOTE_ADDR' => '203.0.113.10',
    ]);
    $route = new Route('POST', '/consultation', fn () => null);
    $route->name('consultation.store');
    $request->setRouteResolver(fn () => $route);

    app(SecurityEventLogger::class)->honeypotTriggered($request);

    $event = SecurityEvent::query()->first();

    expect($event)->not->toBeNull()
        ->and($event->event)->toBe('honeypot_triggered')
        ->and($event->route)->toBe('consultation.store')
        ->and($event->ip)->toBe('203.0.113.10')
        ->and($event->meta)->toBeNull();
});

test('security event logger skips database row when database logging is disabled', function () {
    config(['security.logging.database_enabled' => false]);

    app(SecurityEventLogger::class)->honeypotTriggered();

    expect(SecurityEvent::query()->count())->toBe(0);
});

test('security event logger swallows database exceptions without breaking caller', function () {
    SecurityEvent::creating(function (): void {
        throw new RuntimeException('Database unavailable.');
    });

    try {
        $captured = captureLoggedMessages();

        expect(fn () => app(SecurityEventLogger::class)->honeypotTriggered())
            ->not->toThrow(RuntimeException::class);

        expect($captured->messages)->toHaveCount(1)
            ->and(SecurityEvent::query()->count())->toBe(0);
    } finally {
        SecurityEvent::flushEventListeners();
    }
});

test('security event logger stores event specific meta without envelope duplication', function () {
    app(SecurityEventLogger::class)->paymentRetryCeilingReached(10, 20, 5, 5);

    $event = SecurityEvent::query()->first();

    expect($event)->not->toBeNull()
        ->and($event->meta)->toBe([
            'order_id' => 10,
            'payment_id' => 20,
            'retry_count' => 5,
            'max_retries' => 5,
        ])
        ->and($event->meta)->not->toHaveKey('event')
        ->and($event->meta)->not->toHaveKey('occurred_at')
        ->and($event->meta)->not->toHaveKey('ip');
});
