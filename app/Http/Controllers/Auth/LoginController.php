<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\AttemptToAuthenticate;
use App\Actions\Fortify\NormalizeLoginMobile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailLoginRequest;
use App\Http\Requests\Auth\MobileLoginRequest;
use App\Support\AuthRedirect;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectsIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Features;

class LoginController extends Controller
{
    public function create(Request $request): Response
    {
        AuthRedirect::rememberIntendedFromQuery($request);

        return Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function createEmail(Request $request): Response
    {
        AuthRedirect::rememberIntendedFromQuery($request);

        return Inertia::render('auth/login-email', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(MobileLoginRequest $request): mixed
    {
        return $this->loginPipeline($request)->then(function () {
            return app(LoginResponse::class);
        });
    }

    public function storeEmail(EmailLoginRequest $request): mixed
    {
        return $this->loginPipeline($request)->then(function () {
            return app(LoginResponse::class);
        });
    }

    /**
     * @return Pipeline
     */
    protected function loginPipeline(Request $request)
    {
        return (new Pipeline(app()))->send($request)->through(array_filter([
            config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
            $request->routeIs('login.email.store') ? CanonicalizeUsername::class : NormalizeLoginMobile::class,
            Features::enabled(Features::twoFactorAuthentication()) ? RedirectsIfTwoFactorAuthenticatable::class : null,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]));
    }
}
