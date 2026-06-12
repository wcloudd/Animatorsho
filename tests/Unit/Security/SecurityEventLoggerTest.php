<?php

use App\Services\Security\SecurityEventLogger;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

beforeEach(function () {
    config([
        'security.logging.enabled' => true,
        'security.logging.channel' => 'security',
        'security.logging.user_agent_max_length' => 200,
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

test('auth rate limit exceeded includes limiter and retry after seconds', function () {
    $captured = captureLoggedMessages();

    $request = Request::create('/login', 'POST');
    $route = new Route('POST', '/login', fn () => null);
    $route->name('login.store');
    $request->setRouteResolver(fn () => $route);

    $exception = new TooManyRequestsHttpException(60, 'Too Many Attempts.');

    app(SecurityEventLogger::class)->authRateLimitExceeded($exception, $request);

    expect($captured->messages)->toHaveCount(1)
        ->and($captured->messages[0]->message)->toBe('auth_rate_limit_exceeded')
        ->and($captured->messages[0]->context['event'])->toBe('auth_rate_limit_exceeded')
        ->and($captured->messages[0]->context['limiter'])->toBe('login')
        ->and($captured->messages[0]->context['retry_after_seconds'])->toBe(60);
});
