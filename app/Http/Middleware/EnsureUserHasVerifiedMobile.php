<?php

namespace App\Http\Middleware;

use App\Support\AuthRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasVerifiedMobile
{
    public const REQUIRED_MESSAGE = 'برای ادامه، ابتدا شماره موبایل خود را ثبت و تأیید کنید.';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->hasVerifiedMobile()) {
            return $next($request);
        }

        $intended = AuthRedirect::intendedPathFromRequest($request);

        if ($intended !== null) {
            session(['url.intended' => $intended]);
        }

        return redirect()
            ->route('profile.mobile.create')
            ->with('status', 'mobile-verification-required');
    }
}
