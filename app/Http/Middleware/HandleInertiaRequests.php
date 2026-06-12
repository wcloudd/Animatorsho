<?php

namespace App\Http\Middleware;

use App\Services\Course\CourseAccessService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $hasCourseAccess = $user !== null
            && app(CourseAccessService::class)->userHasActiveAccess($user);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'appUrl' => rtrim((string) config('app.url'), '/'),
            'auth' => [
                'user' => $user,
                'isAdmin' => $user?->isAdmin() ?? false,
            ],
            'nav' => [
                'animatorshoHref' => $hasCourseAccess
                    ? route('course.home')
                    : route('home'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'security' => [
                'honeypot' => [
                    'enabled' => (bool) config('security.honeypot.enabled', true),
                    'fieldName' => (string) config('security.honeypot.field_name'),
                ],
            ],
        ];
    }
}
