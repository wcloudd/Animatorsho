<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangeRegistrationMobileRequest;
use App\Http\Requests\Auth\StoreRegistrationRequest;
use App\Http\Requests\Auth\VerifyRegistrationOtpRequest;
use App\Services\Auth\MobileOtpAuthService;
use App\Services\Auth\RegistrationCompletionService;
use App\Support\AuthRedirect;
use App\Support\IranianMobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(
        private readonly RegistrationCompletionService $registration,
        private readonly MobileOtpAuthService $mobileOtpAuth,
    ) {}

    public function create(Request $request): Response
    {
        AuthRedirect::rememberIntendedFromQuery($request);

        $pending = $this->registration->pendingRegistration($request);

        return Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'pendingRegistration' => $pending !== null ? [
                'name' => $pending['name'],
                'mobile' => $pending['mobile'],
                'email' => $pending['email'],
            ] : null,
        ]);
    }

    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        $this->registration->storePendingAndSendCode(
            $request->validated(),
            $request,
        );

        return redirect()
            ->route('register.verify')
            ->with('status', 'otp-sent');
    }

    public function verifyForm(Request $request): Response|RedirectResponse
    {
        AuthRedirect::rememberIntendedFromQuery($request);

        $mobile = $this->registration->pendingMobile($request);

        if ($mobile === null || $this->registration->pendingRegistration($request) === null) {
            return redirect()->route('register');
        }

        $sentAt = $request->session()->get('registration_otp.sent_at');
        $resendAvailableAt = $this->mobileOtpAuth->resendAvailableAt(
            is_string($sentAt) ? $sentAt : null,
        );

        return Inertia::render('auth/register-verify', [
            'maskedMobile' => IranianMobile::mask($mobile),
            'resendAvailableAt' => $resendAvailableAt?->toIso8601String(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function verify(VerifyRegistrationOtpRequest $request): RedirectResponse
    {
        $mobile = $this->registration->pendingMobile($request);

        if ($mobile === null) {
            return redirect()->route('register');
        }

        $user = $this->registration->verifyAndComplete(
            $request->validated('code'),
            $request,
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }

    public function resendCode(Request $request): RedirectResponse
    {
        if ($this->registration->pendingMobile($request) === null) {
            return redirect()->route('register');
        }

        $this->registration->resendCode($request);

        return redirect()
            ->route('register.verify')
            ->with('status', 'otp-sent');
    }

    public function changeMobile(ChangeRegistrationMobileRequest $request): RedirectResponse
    {
        if ($this->registration->pendingRegistration($request) === null) {
            return redirect()->route('register');
        }

        $this->registration->changeMobile(
            $request->validated('mobile'),
            $request,
        );

        return redirect()
            ->route('register.verify')
            ->with('status', 'otp-sent');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->registration->clearPending($request);

        return redirect()->route('register');
    }
}
