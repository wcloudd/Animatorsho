<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendPasswordResetOtpRequest;
use App\Http\Requests\Auth\UpdatePasswordAfterMobileResetRequest;
use App\Http\Requests\Auth\VerifyPasswordResetOtpRequest;
use App\Models\User;
use App\Services\Auth\MobileOtpAuthService;
use App\Support\IranianMobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetMobileController extends Controller
{
    public function __construct(
        private readonly MobileOtpAuthService $mobileOtpAuth,
        private readonly ResetUserPassword $resetUserPassword,
    ) {}

    public function sendCode(SendPasswordResetOtpRequest $request): RedirectResponse
    {
        $this->mobileOtpAuth->sendPasswordResetCode(
            $request->validated('mobile'),
            $request,
        );

        return redirect()
            ->route('password.mobile.verify')
            ->with('status', 'otp-sent');
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get('password_reset_otp.mobile');

        if (! is_string($mobile) || $mobile === '') {
            return redirect()->route('password.request');
        }

        $this->mobileOtpAuth->sendPasswordResetCode($mobile, $request);

        return redirect()
            ->route('password.mobile.verify')
            ->with('status', 'otp-sent');
    }

    public function verifyForm(Request $request): Response|RedirectResponse
    {
        $mobile = $request->session()->get('password_reset_otp.mobile');

        if (! is_string($mobile) || $mobile === '') {
            return redirect()->route('password.request');
        }

        $sentAt = $request->session()->get('password_reset_otp.sent_at');
        $resendAvailableAt = $this->mobileOtpAuth->resendAvailableAt(
            is_string($sentAt) ? $sentAt : null,
        );

        return Inertia::render('auth/forgot-password-verify', [
            'maskedMobile' => IranianMobile::mask($mobile),
            'resendAvailableAt' => $resendAvailableAt?->toIso8601String(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function verify(VerifyPasswordResetOtpRequest $request): RedirectResponse
    {
        $mobile = $request->session()->get('password_reset_otp.mobile');

        if (! is_string($mobile) || $mobile === '') {
            return redirect()->route('password.request');
        }

        $user = $this->mobileOtpAuth->verifyPasswordResetCode(
            $mobile,
            $request->validated('code'),
        );

        $request->session()->put('password_reset.user_id', $user->id);
        $request->session()->forget(['password_reset_otp.mobile', 'password_reset_otp.sent_at']);

        return redirect()->route('password.mobile.reset');
    }

    public function resetForm(Request $request): Response|RedirectResponse
    {
        $userId = $this->resolvePasswordResetUserId($request);

        if ($userId === null) {
            return redirect()->route('password.request');
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            $request->session()->forget('password_reset.user_id');

            return redirect()->route('password.request');
        }

        return Inertia::render('auth/reset-password-mobile', [
            'maskedMobile' => $user->mobile !== null ? IranianMobile::mask($user->mobile) : null,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function reset(UpdatePasswordAfterMobileResetRequest $request): RedirectResponse
    {
        $userId = $this->resolvePasswordResetUserId($request);

        if ($userId === null) {
            return redirect()->route('password.request');
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            $request->session()->forget('password_reset.user_id');

            return redirect()->route('password.request');
        }

        $this->resetUserPassword->reset($user, $request->validated());
        $request->session()->forget('password_reset.user_id');

        return redirect()
            ->route('login')
            ->with('status', __('Your password has been reset.'));
    }

    private function resolvePasswordResetUserId(Request $request): ?int
    {
        $userId = $request->session()->get('password_reset.user_id');

        if (is_int($userId)) {
            return $userId;
        }

        if (is_string($userId) && ctype_digit($userId)) {
            return (int) $userId;
        }

        return null;
    }
}
