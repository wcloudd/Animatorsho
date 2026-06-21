<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\AttemptToAuthenticate;
use App\Actions\Fortify\NormalizeLoginMobile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailLoginRequest;
use App\Http\Requests\Auth\MobileLoginRequest;
use App\Http\Requests\Auth\SubmitAuthIdentifierRequest;
use App\Services\Auth\AuthIdentifierService;
use App\Services\Auth\RegistrationCompletionService;
use App\Support\AuthIdentifier;
use App\Support\AuthRedirect;
use App\Support\IranianMobile;
use Illuminate\Http\RedirectResponse;
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

    public function createPassword(Request $request): Response|RedirectResponse
    {
        AuthRedirect::rememberIntendedFromQuery($request);

        $mobile = $request->session()->get('mobile_otp.mobile');

        if (! is_string($mobile) || $mobile === '') {
            return redirect()->route('login');
        }

        return Inertia::render('auth/login-password', [
            'maskedMobile' => IranianMobile::mask($mobile),
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function createEmail(Request $request): Response
    {
        AuthRedirect::rememberIntendedFromQuery($request);

        $email = $request->query('email');

        return Inertia::render('auth/login-email', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
            'prefilledEmail' => is_string($email) && $email !== '' ? strtolower($email) : null,
        ]);
    }

    public function resolveIdentifier(
        SubmitAuthIdentifierRequest $request,
        AuthIdentifierService $authIdentifier,
        RegistrationCompletionService $registration,
    ): RedirectResponse {
        $parsed = AuthIdentifier::parse($request->validated('identifier'));

        if ($parsed === null) {
            return back();
        }

        $resolution = $authIdentifier->resolve($parsed);

        if ($resolution['action'] === AuthIdentifierService::ACTION_MOBILE_OTP_LOGIN) {
            $request->session()->put('mobile_otp.mobile', $resolution['mobile']);

            return redirect()->route('login.password');
        }

        if ($resolution['action'] === AuthIdentifierService::ACTION_REGISTRATION) {
            $registration->storePendingAuthMobile($resolution['mobile'], $request);

            return redirect()->route('register');
        }

        return redirect()->route('login.email', [
            'email' => $resolution['email'],
        ]);
    }

    public function store(MobileLoginRequest $request): mixed
    {
        return $this->loginPipeline($request)->then(function () use ($request) {
            $request->session()->forget(['mobile_otp.mobile', 'mobile_otp.sent_at']);

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
            EnsureLoginIsNotThrottled::class,
            $request->routeIs('login.email.store') ? CanonicalizeUsername::class : NormalizeLoginMobile::class,
            Features::enabled(Features::twoFactorAuthentication()) ? RedirectsIfTwoFactorAuthenticatable::class : null,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]));
    }
}
