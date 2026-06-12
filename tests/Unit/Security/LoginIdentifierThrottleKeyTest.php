<?php

use App\Support\LoginIdentifier;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * @return array{0: Request, 1: Route}
 */
function loginThrottleRequest(string $routeName, array $payload = []): array
{
    $route = tap(new Route(['POST'], '/login', []), function (Route $route) use ($routeName): void {
        $route->name($routeName);
    });

    $request = Request::create('/login', 'POST', $payload);
    $request->setRouteResolver(fn () => $route);

    return [$request, $route];
}

test('login throttle key uses session mobile when request omits mobile', function () {
    [$request] = loginThrottleRequest('login.store', [
        'password' => 'secret',
    ]);
    $request->setLaravelSession(app('session.store'));
    $request->session()->start();
    $request->session()->put('mobile_otp.mobile', '09121234567');

    expect(LoginIdentifier::throttleKey($request))->toBe('09121234567|127.0.0.1');
});

test('login throttle key uses request mobile when provided', function () {
    [$request] = loginThrottleRequest('login.store', [
        'mobile' => '09121234567',
        'password' => 'secret',
    ]);

    expect(LoginIdentifier::throttleKey($request))->toBe('09121234567|127.0.0.1');
});

test('email login throttle key uses email not session mobile', function () {
    [$request] = loginThrottleRequest('login.email.store', [
        'email' => 'User@Example.com',
        'password' => 'secret',
    ]);
    $request->setLaravelSession(app('session.store'));
    $request->session()->start();
    $request->session()->put('mobile_otp.mobile', '09121234567');

    expect(LoginIdentifier::throttleKey($request))->toBe('user@example.com|127.0.0.1');
});
