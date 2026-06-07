<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureUserHasVerifiedMobile;
use App\Http\Requests\Profile\SendProfileMobileOtpRequest;
use App\Http\Requests\Profile\VerifyProfileMobileOtpRequest;
use App\Services\Auth\MobileOtpAuthService;
use App\Support\IranianMobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileMobileVerificationController extends Controller
{
    public function __construct(
        private readonly MobileOtpAuthService $mobileOtpAuth,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && $user->hasVerifiedMobile()) {
            return redirect()->intended(route('profile', absolute: false));
        }

        return Inertia::render('profile/mobile', [
            'status' => $request->session()->get('status'),
            'message' => $request->session()->get('flash.banner'),
            'existingMobile' => $user?->mobile,
            'maskedExistingMobile' => IranianMobile::mask($user?->mobile),
        ]);
    }

    public function sendExistingCode(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null || ! filled($user->mobile)) {
            return redirect()->route('profile.mobile.create');
        }

        $this->mobileOtpAuth->sendVerificationCode(
            $user,
            $user->mobile,
            $request,
        );

        return redirect()
            ->route('profile.mobile.verify')
            ->with('status', 'otp-sent');
    }

    public function sendCode(SendProfileMobileOtpRequest $request): RedirectResponse
    {
        $this->mobileOtpAuth->sendVerificationCode(
            $request->user(),
            $request->validated('mobile'),
            $request,
        );

        return redirect()
            ->route('profile.mobile.verify')
            ->with('status', 'otp-sent');
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get('mobile_verification.mobile');

        if (! is_string($mobile) || $mobile === '') {
            return redirect()->route('profile.mobile.create');
        }

        $this->mobileOtpAuth->sendVerificationCode(
            $request->user(),
            $mobile,
            $request,
        );

        return redirect()
            ->route('profile.mobile.verify')
            ->with('status', 'otp-sent');
    }

    public function verifyForm(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && $user->hasVerifiedMobile()) {
            return redirect()->intended(route('profile', absolute: false));
        }

        $mobile = $request->session()->get('mobile_verification.mobile');

        if (! is_string($mobile) || $mobile === '') {
            return redirect()->route('profile.mobile.create');
        }

        $sentAt = $request->session()->get('mobile_verification.sent_at');
        $resendAvailableAt = $this->mobileOtpAuth->resendAvailableAt(
            is_string($sentAt) ? $sentAt : null,
        );

        return Inertia::render('profile/mobile-verify', [
            'maskedMobile' => IranianMobile::mask($mobile),
            'resendAvailableAt' => $resendAvailableAt?->toIso8601String(),
            'status' => $request->session()->get('status'),
            'message' => EnsureUserHasVerifiedMobile::REQUIRED_MESSAGE,
        ]);
    }

    public function verify(VerifyProfileMobileOtpRequest $request): RedirectResponse
    {
        $mobile = $request->session()->get('mobile_verification.mobile');

        if (! is_string($mobile) || $mobile === '') {
            return redirect()->route('profile.mobile.create');
        }

        $this->mobileOtpAuth->verifyVerificationCode(
            $request->user(),
            $mobile,
            $request->validated('code'),
        );

        $request->session()->forget(['mobile_verification.mobile', 'mobile_verification.sent_at']);

        return redirect()
            ->intended(route('profile', absolute: false))
            ->with('status', 'mobile-verified');
    }
}
