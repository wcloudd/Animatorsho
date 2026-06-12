<?php

namespace App\Services\Auth;

use App\Enums\OtpPurpose;
use App\Enums\SmsMessageType;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Sms\SmsService;
use App\Services\Sms\SmsSettingsService;
use App\Services\Sms\SmsTemplateService;
use App\Support\IranianMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileOtpAuthService
{
    public const NO_ACCOUNT_MESSAGE = 'برای ساخت حساب جدید، ابتدا ثبت‌نام کنید.';

    public const SMS_UNAVAILABLE_MESSAGE = 'بازیابی با شماره موبایل فعلاً در دسترس نیست. اگر ایمیل بازیابی ثبت کرده‌اید، از بازیابی با ایمیل استفاده کنید.';

    public function __construct(
        private readonly SmsService $sms,
        private readonly SmsTemplateService $templates,
        private readonly SmsSettingsService $smsSettings,
    ) {}

    public function sendLoginCode(string $mobile, Request $request): void
    {
        $normalizedMobile = IranianMobile::normalize($mobile);

        if ($normalizedMobile === null) {
            throw ValidationException::withMessages([
                'mobile' => IranianMobile::validationMessage($mobile),
            ]);
        }

        $this->assertExistingUserForLogin($normalizedMobile);

        $this->assertResendCooldown($normalizedMobile, OtpPurpose::Login);

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

    public function sendRegistrationCode(string $mobile, Request $request): void
    {
        $normalizedMobile = IranianMobile::normalize($mobile);

        if ($normalizedMobile === null) {
            throw ValidationException::withMessages([
                'mobile' => IranianMobile::validationMessage($mobile),
            ]);
        }

        $this->assertResendCooldown($normalizedMobile, OtpPurpose::Registration);

        $this->invalidateActiveCodes($normalizedMobile, OtpPurpose::Registration);

        $code = $this->generateCode();
        $expiresMinutes = (int) config('otp.expires_minutes', 5);

        OtpCode::query()->create([
            'mobile' => $normalizedMobile,
            'code_hash' => Hash::make($code),
            'purpose' => OtpPurpose::Registration,
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
            ['purpose' => OtpPurpose::Registration->value],
        );

        $request->session()->put('registration_otp.mobile', $normalizedMobile);
        $request->session()->put('registration_otp.sent_at', now()->toIso8601String());
    }

    public function verifyRegistrationCode(string $mobile, string $code): void
    {
        $normalizedMobile = IranianMobile::normalize($mobile);

        if ($normalizedMobile === null) {
            throw ValidationException::withMessages([
                'code' => 'کد نامعتبر یا منقضی است.',
            ]);
        }

        $otpCode = OtpCode::query()
            ->forMobile($normalizedMobile, OtpPurpose::Registration)
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

        return $this->findLoginUser($normalizedMobile);
    }

    public function sendPasswordResetCode(string $mobile, Request $request): void
    {
        $this->assertOtpDeliveryAvailable();

        $normalizedMobile = IranianMobile::normalize($mobile);

        if ($normalizedMobile === null) {
            throw ValidationException::withMessages([
                'mobile' => IranianMobile::validationMessage($mobile),
            ]);
        }

        $user = User::query()->where('mobile', $normalizedMobile)->first();

        if ($user !== null && $user->hasVerifiedMobile()) {
            $this->assertResendCooldown($normalizedMobile, OtpPurpose::PasswordReset);

            $this->invalidateActiveCodes($normalizedMobile, OtpPurpose::PasswordReset);

            $code = $this->generateCode();
            $expiresMinutes = (int) config('otp.expires_minutes', 5);

            OtpCode::query()->create([
                'mobile' => $normalizedMobile,
                'code_hash' => Hash::make($code),
                'purpose' => OtpPurpose::PasswordReset,
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
                ['purpose' => OtpPurpose::PasswordReset->value],
            );
        }

        $request->session()->put('password_reset_otp.mobile', $normalizedMobile);
        $request->session()->put('password_reset_otp.sent_at', now()->toIso8601String());
    }

    public function verifyPasswordResetCode(string $mobile, string $code): User
    {
        $normalizedMobile = IranianMobile::normalize($mobile);

        if ($normalizedMobile === null) {
            throw ValidationException::withMessages([
                'code' => 'کد نامعتبر یا منقضی است.',
            ]);
        }

        $otpCode = OtpCode::query()
            ->forMobile($normalizedMobile, OtpPurpose::PasswordReset)
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

        $user = User::query()->where('mobile', $normalizedMobile)->first();

        if ($user === null || ! $user->hasVerifiedMobile()) {
            throw ValidationException::withMessages([
                'code' => 'کد نامعتبر یا منقضی است.',
            ]);
        }

        return $user;
    }

    public function sendVerificationCode(User $user, string $mobile, Request $request): void
    {
        $normalizedMobile = IranianMobile::normalize($mobile);

        if ($normalizedMobile === null) {
            throw ValidationException::withMessages([
                'mobile' => IranianMobile::validationMessage($mobile),
            ]);
        }

        $this->assertMobileAvailableForUser($user, $normalizedMobile);

        if (filled($user->mobile) && $user->mobile !== $normalizedMobile) {
            throw ValidationException::withMessages([
                'mobile' => 'تغییر شماره موبایل از این بخش امکان‌پذیر نیست. با پشتیبانی تماس بگیرید.',
            ]);
        }

        $this->assertResendCooldown($normalizedMobile, OtpPurpose::Verification);

        $this->invalidateActiveCodes($normalizedMobile, OtpPurpose::Verification);

        $code = $this->generateCode();
        $expiresMinutes = (int) config('otp.expires_minutes', 5);

        OtpCode::query()->create([
            'mobile' => $normalizedMobile,
            'code_hash' => Hash::make($code),
            'purpose' => OtpPurpose::Verification,
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
            ['purpose' => OtpPurpose::Verification->value],
        );

        $request->session()->put('mobile_verification.mobile', $normalizedMobile);
        $request->session()->put('mobile_verification.sent_at', now()->toIso8601String());
    }

    public function verifyVerificationCode(User $user, string $mobile, string $code): void
    {
        $normalizedMobile = IranianMobile::normalize($mobile);

        if ($normalizedMobile === null) {
            throw ValidationException::withMessages([
                'code' => 'کد نامعتبر یا منقضی است.',
            ]);
        }

        $this->assertMobileAvailableForUser($user, $normalizedMobile);

        if (filled($user->mobile) && $user->mobile !== $normalizedMobile) {
            throw ValidationException::withMessages([
                'code' => 'کد نامعتبر یا منقضی است.',
            ]);
        }

        $otpCode = OtpCode::query()
            ->forMobile($normalizedMobile, OtpPurpose::Verification)
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

        $user->forceFill([
            'mobile' => $normalizedMobile,
            'mobile_verified_at' => now(),
        ])->save();
    }

    public function findLoginUser(string $mobile): User
    {
        $user = User::query()->where('mobile', $mobile)->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'code' => self::NO_ACCOUNT_MESSAGE,
            ]);
        }

        if ($user->mobile_verified_at === null) {
            $user->forceFill(['mobile_verified_at' => now()])->save();
        }

        return $user;
    }

    public function resendAvailableAt(?string $sentAt): ?Carbon
    {
        if ($sentAt === null || $sentAt === '') {
            return null;
        }

        $sent = Carbon::parse($sentAt);
        $availableAt = $sent->copy()->addSeconds((int) config('otp.resend_cooldown_seconds', 120));

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

    private function assertResendCooldown(string $mobile, OtpPurpose $purpose): void
    {
        $latest = OtpCode::query()
            ->forMobile($mobile, $purpose)
            ->latest('id')
            ->first();

        if ($latest === null) {
            return;
        }

        $cooldownSeconds = (int) config('otp.resend_cooldown_seconds', 120);
        $availableAt = $latest->created_at->copy()->addSeconds($cooldownSeconds);

        if ($availableAt->isFuture()) {
            throw ValidationException::withMessages([
                'mobile' => 'لطفاً چند لحظه صبر کنید و دوباره تلاش کنید.',
            ]);
        }
    }

    private function assertMobileAvailableForUser(User $user, string $mobile): void
    {
        $existingUser = User::query()->where('mobile', $mobile)->first();

        if ($existingUser !== null && $existingUser->id !== $user->id) {
            throw ValidationException::withMessages([
                'mobile' => 'این شماره موبایل قبلاً ثبت شده است.',
            ]);
        }
    }

    private function assertExistingUserForLogin(string $mobile): void
    {
        if (! User::query()->where('mobile', $mobile)->exists()) {
            throw ValidationException::withMessages([
                'mobile' => self::NO_ACCOUNT_MESSAGE,
            ]);
        }
    }

    private function assertOtpDeliveryAvailable(): void
    {
        if (! $this->smsSettings->isOtpDeliveryAvailable()) {
            throw ValidationException::withMessages([
                'mobile' => self::SMS_UNAVAILABLE_MESSAGE,
            ]);
        }
    }
}
