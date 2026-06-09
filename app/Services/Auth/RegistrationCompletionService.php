<?php

namespace App\Services\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Support\IranianMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegistrationCompletionService
{
    use ProfileValidationRules;

    public const SESSION_PENDING_KEY = 'registration.pending';

    public const SESSION_AUTH_PENDING_MOBILE_KEY = 'auth.pending_mobile';

    public function __construct(
        private readonly MobileOtpAuthService $mobileOtpAuth,
        private readonly CreateNewUser $createNewUser,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function storePendingAuthMobile(string $mobile, Request $request): void
    {
        $normalizedMobile = IranianMobile::normalize($mobile);

        if ($normalizedMobile === null) {
            throw ValidationException::withMessages([
                'identifier' => 'شماره موبایل معتبر وارد کنید (مثال: 09123456789).',
            ]);
        }

        $this->clearPending($request);

        $request->session()->put(self::SESSION_AUTH_PENDING_MOBILE_KEY, $normalizedMobile);
    }

    public function pendingAuthMobile(Request $request): ?string
    {
        $mobile = $request->session()->get(self::SESSION_AUTH_PENDING_MOBILE_KEY);

        return is_string($mobile) && $mobile !== '' ? $mobile : null;
    }

    public function storePendingAndSendCode(array $input, Request $request): void
    {
        if (isset($input['mobile']) && is_string($input['mobile'])) {
            $normalized = IranianMobile::normalize($input['mobile']);

            if ($normalized !== null) {
                $input['mobile'] = $normalized;
            }
        }

        if (isset($input['email']) && is_string($input['email'])) {
            $email = trim($input['email']);
            $input['email'] = $email !== '' ? strtolower($email) : null;
        }

        $mobile = $this->normalizedMobileFromInput($input)
            ?? $this->pendingAuthMobile($request);

        if ($mobile === null) {
            throw ValidationException::withMessages([
                'mobile' => 'شماره موبایل معتبر وارد کنید (مثال: 09123456789).',
            ]);
        }

        $request->session()->forget(self::SESSION_AUTH_PENDING_MOBILE_KEY);

        $request->session()->put(self::SESSION_PENDING_KEY, [
            'name' => $input['name'],
            'username' => $input['username'],
            'mobile' => $mobile,
            'email' => $input['email'] ?? null,
            'password' => Crypt::encryptString($input['password']),
        ]);

        $this->mobileOtpAuth->sendRegistrationCode($mobile, $request);
    }

    public function verifyAndComplete(string $code, Request $request): User
    {
        $pending = $this->pendingRegistration($request);
        $mobile = $this->pendingMobile($request);

        if ($pending === null || $mobile === null) {
            throw ValidationException::withMessages([
                'code' => 'کد نامعتبر یا منقضی است.',
            ]);
        }

        $this->mobileOtpAuth->verifyRegistrationCode($mobile, $code);

        $password = Crypt::decryptString($pending['password']);

        $user = $this->createNewUser->createVerifiedUser([
            'name' => $pending['name'],
            'username' => $pending['username'],
            'email' => $pending['email'],
            'mobile' => $mobile,
            'password' => $password,
        ]);

        $this->clearPending($request);

        return $user;
    }

    public function resendCode(Request $request): void
    {
        $mobile = $this->pendingMobile($request);

        if ($mobile === null || $this->pendingRegistration($request) === null) {
            throw ValidationException::withMessages([
                'mobile' => 'ابتدا فرم ثبت‌نام را تکمیل کنید.',
            ]);
        }

        $this->mobileOtpAuth->sendRegistrationCode($mobile, $request);
    }

    public function changeMobile(string $mobile, Request $request): void
    {
        $pending = $this->pendingRegistration($request);

        if ($pending === null) {
            throw ValidationException::withMessages([
                'mobile' => 'ابتدا فرم ثبت‌نام را تکمیل کنید.',
            ]);
        }

        $normalizedMobile = IranianMobile::normalize($mobile);

        if ($normalizedMobile === null) {
            throw ValidationException::withMessages([
                'mobile' => 'شماره موبایل معتبر وارد کنید (مثال: 09123456789).',
            ]);
        }

        Validator::make(
            ['mobile' => $normalizedMobile],
            ['mobile' => $this->mobileRules()],
        )->validate();

        $pending['mobile'] = $normalizedMobile;
        $request->session()->put(self::SESSION_PENDING_KEY, $pending);

        $this->mobileOtpAuth->sendRegistrationCode($normalizedMobile, $request);
    }

    public function clearPending(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_PENDING_KEY,
            self::SESSION_AUTH_PENDING_MOBILE_KEY,
            'registration_otp.mobile',
            'registration_otp.sent_at',
        ]);
    }

    public function pendingMobile(Request $request): ?string
    {
        $mobile = $request->session()->get('registration_otp.mobile');

        return is_string($mobile) && $mobile !== '' ? $mobile : null;
    }

    /**
     * @return array{name: string, username: string, mobile: string, email: ?string, password: string}|null
     */
    public function pendingRegistration(Request $request): ?array
    {
        $pending = $request->session()->get(self::SESSION_PENDING_KEY);

        if (! is_array($pending)) {
            return null;
        }

        if (
            ! isset($pending['name'], $pending['username'], $pending['mobile'], $pending['password'])
            || ! is_string($pending['name'])
            || ! is_string($pending['username'])
            || ! is_string($pending['mobile'])
            || ! is_string($pending['password'])
        ) {
            return null;
        }

        return [
            'name' => $pending['name'],
            'username' => $pending['username'],
            'mobile' => $pending['mobile'],
            'email' => isset($pending['email']) && is_string($pending['email']) && $pending['email'] !== ''
                ? $pending['email']
                : null,
            'password' => $pending['password'],
        ];
    }
}
