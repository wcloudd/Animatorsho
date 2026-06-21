<?php

namespace App\Http\Middleware;

use App\Services\SiteSettingsService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiteNotInMaintenance
{
    public function __construct(
        private readonly SiteSettingsService $siteSettings,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->siteSettings->isMaintenanceModeEnabled()) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(503, $this->siteSettings->maintenanceTitle());
        }

        return Inertia::render('maintenance/index', [
            'title' => $this->siteSettings->maintenanceTitle(),
            'message' => $this->siteSettings->maintenanceMessage(),
        ])->toResponse($request)->setStatusCode(503);
    }

    private function shouldBypass(Request $request): bool
    {
        if ($request->user()?->isAdmin()) {
            return true;
        }

        return $request->is(
            'admin',
            'admin/*',
            'login',
            'login/*',
            'logout',
            'register',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'password/mobile',
            'password/mobile/*',
            'auth/mobile',
            'auth/mobile/*',
            'up',
            'build/*',
            'checkout/zarinpal/callback',
        );
    }
}
