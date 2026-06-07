<?php

namespace App\Services\Auth;

use App\Enums\OtpPurpose;
use App\Enums\SmsMessageType;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Sms\SmsService;
use App\Services\Sms\SmsTemplateService;
use App\Support\IranianMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileOtpAuthService
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly SmsTemplateService $templates,
    ) {}

    public function sendLoginCode(string $mobile, Request $request): void
    {
        $normalizedMobile = IranianMobile::normalize($mobile);

        if ($normalizedMobile === null) {
            throw ValidationException::withMessages([
                'mobile' => 'شماره موبایل معتبر وارد کنید (مثال: 09123456789).',
            ]);
        }

        $this->assertResendCooldown($normalizedMobile);

        $this->invalidateActiveCodes($normalizedMobile, OtpPurpose::Login);

        $code = $this->generateCode();
        $expiresMinutes = (int) config('otp.expires_minutes', 5);

        OtpCode::query()->create([
            'mobile' => $normalizedMobile,
            'code_hash' => Hash::make($code),
            'purpose' => OtpPurpose::Login,
            'expires_at' => now()->addMinutes($expiresMinutes),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $message = $this->templates->render(SmsMessageType::OtpLogin, [
            'code' => $code,
        ]);

        $this->sms->send(
            $normalizedMobile,
            $message,
            SmsMessageType::OtpLogin,
            ['purpose' => OtpPurpose::Login->value],
        );

        $request->session()->put('mobile_otp.mobile', $normalizedMobile);
        $request->session()->put('mobile_otp.sent_at', now()->toIso8601String());
    }

    public function verifyLoginCode(string $mobile, string $code): User
    {
        $normalizedMobile = IranianMobile::normalize($mobile);

        if ($normalizedMobile === null) {
            throw ValidationException::withMessages([
                'code' => 'کد نامعتبر یا منقضی است.',
            ]);
        }

        $otpCode = OtpCode::query()
            ->forMobile($normalizedMobile, OtpPurpose::Login)
            ->active()
            ->latest('id')
            ->first();

        if ($otpCode === null) {
            throw ValidationException::withMessages([
                'code' => 'کد نامعتبر یا منقضی است.',
            ]);
        }

        if ($otpCode->hasExceededAttempts()) {
            throw ValidationException::withMessages([
                'code' => 'کد نامعتبر یا منقضی است.',
            ]);
        }

        if (! Hash::check($code, $otpCode->code_hash)) {
            $otpCode->increment('attempts');

            throw ValidationException::withMessages([
                'code' => 'کد نامعتبر یا منقضی است.',
            ]);
        }

        $otpCode->update(['consumed_at' => now()]);

        return $this->findOrCreateUser($normalizedMobile);
    }

    public function findOrCreateUser(string $mobile): User
    {
        $user = User::query()->where('mobile', $mobile)->first();

        if ($user !== null) {
            if ($user->mobile_verified_at === null) {
                $user->forceFill(['mobile_verified_at' => now()])->save();
            }

            return $user;
        }

        return User::query()->create([
            'name' => config('otp.default_user_name', 'کاربر انیماتورشو'),
            'email' => null,
            'password' => null,
            'mobile' => $mobile,
            'mobile_verified_at' => now(),
            'is_admin' => false,
        ]);
    }

    public function resendAvailableAt(?string $sentAt): ?Carbon
    {
        if ($sentAt === null || $sentAt === '') {
            return null;
        }

        $sent = Carbon::parse($sentAt);
        $availableAt = $sent->copy()->addSeconds((int) config('otp.resend_cooldown_seconds', 60));

        if ($availableAt->isPast()) {
            return null;
        }

        return $availableAt;
    }

    private function generateCode(): string
    {
        $length = (int) config('otp.code_length', 6);
        $max = (10 ** $length) - 1;
        $min = 10 ** ($length - 1);

        return (string) random_int($min, $max);
    }

    private function invalidateActiveCodes(string $mobile, OtpPurpose $purpose): void
    {
        OtpCode::query()
            ->forMobile($mobile, $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }

    private function assertResendCooldown(string $mobile): void
    {
        $latest = OtpCode::query()
            ->forMobile($mobile, OtpPurpose::Login)
            ->latest('id')
            ->first();

        if ($latest === null) {
            return;
        }

        $cooldownSeconds = (int) config('otp.resend_cooldown_seconds', 60);
        $availableAt = $latest->created_at->copy()->addSeconds($cooldownSeconds);

        if ($availableAt->isFuture()) {
            throw ValidationException::withMessages([
                'mobile' => 'لطفاً چند لحظه صبر کنید و دوباره تلاش کنید.',
            ]);
        }
    }
}
