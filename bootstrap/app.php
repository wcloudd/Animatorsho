<?php

use App\Http\Middleware\EnsureSiteNotInMaintenance;
use App\Http\Middleware\EnsureUserHasVerifiedMobile;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RejectHoneypotSubmission;
use App\Http\Middleware\SetRobotsIndexingHeader;
use App\Services\Security\SecurityEventLogger;
use App\Support\AuthThrottleMessage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxyConfig = require __DIR__.'/../config/trustedproxy.php';

        $middleware->trustProxies(
            at: $trustedProxyConfig['proxies'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            SetRobotsIndexingHeader::class,
            EnsureSiteNotInMaintenance::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'honeypot' => RejectHoneypotSubmission::class,
            'verified.mobile' => EnsureUserHasVerifiedMobile::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) {
            if (! AuthThrottleMessage::appliesTo($request)) {
                return null;
            }

            app(SecurityEventLogger::class)->authRateLimitExceeded($exception, $request);

            return redirect()
                ->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'throttle' => AuthThrottleMessage::forException($exception),
                ]);
        });
    })->create();
