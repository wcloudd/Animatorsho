<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendMobileOtpRequest;
use App\Http\Requests\Auth\VerifyMobileOtpRequest;
use App\Services\Auth\MobileOtpAuthService;
use App\Support\AuthRedirect;
use App\Support\IranianMobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MobileAuthController extends Controller
{
    public function __construct(
        private readonly MobileOtpAuthService $mobileOtpAuth,
    ) {}

    public function create(Request $request): Response
    {
        AuthRedirect::rememberIntendedFromQuery($request);

        return Inertia::render('auth/mobile', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function sendCode(SendMobileOtpRequest $request): RedirectResponse
    {
        $this->mobileOtpAuth->sendLoginCode(
            $request->validated('mobile'),
            $request,
        );

        return redirect()
            ->route('auth.mobile.verify')
            ->with('status', 'otp-sent');
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get('mobile_otp.mobile');

        if (! is_string($mobile) || $mobile === '') {
            return redirect()->route('auth.mobile.create');
        }

        $this->mobileOtpAuth->sendLoginCode($mobile, $request);

        return redirect()
            ->route('auth.mobile.verify')
            ->with('status', 'otp-sent');
    }

    public function verifyForm(Request $request): Response|RedirectResponse
    {
        AuthRedirect::rememberIntendedFromQuery($request);

        $mobile = $request->session()->get('mobile_otp.mobile');

        if (! is_string($mobile) || $mobile === '') {
            return redirect()->route('auth.mobile.create');
        }

        $sentAt = $request->session()->get('mobile_otp.sent_at');
        $resendAvailableAt = $this->mobileOtpAuth->resendAvailableAt(
            is_string($sentAt) ? $sentAt : null,
        );

        return Inertia::render('auth/mobile-verify', [
            'maskedMobile' => IranianMobile::mask($mobile),
            'resendAvailableAt' => $resendAvailableAt?->toIso8601String(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function verify(VerifyMobileOtpRequest $request): RedirectResponse
    {
        $mobile = $request->session()->get('mobile_otp.mobile');

        if (! is_string($mobile) || $mobile === '') {
            return redirect()->route('auth.mobile.create');
        }

        $user = $this->mobileOtpAuth->verifyLoginCode(
            $mobile,
            $request->validated('code'),
        );

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['mobile_otp.mobile', 'mobile_otp.sent_at']);

        return redirect()->intended(route('home', absolute: false));
    }
}
