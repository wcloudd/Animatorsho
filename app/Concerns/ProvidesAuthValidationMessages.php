<?php

namespace App\Concerns;

use App\Support\AuthIdentifier;
use App\Support\IranianMobile;

trait ProvidesAuthValidationMessages
{
    /**
     * @return array<string, string>
     */
    protected function authPasswordRequiredMessages(): array
    {
        return [
            'password.required' => 'رمز عبور را وارد کنید.',
            'password_confirmation.required' => 'تکرار رمز عبور را وارد کنید.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function authMobileRequiredMessages(): array
    {
        return [
            'mobile.required' => IranianMobile::EMPTY_MESSAGE,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function authIdentifierRequiredMessages(): array
    {
        return [
            'identifier.required' => AuthIdentifier::validationMessage(''),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function authRegistrationRequiredMessages(): array
    {
        return [
            'name.required' => 'نام و نام خانوادگی را وارد کنید.',
            'username.required' => 'نام کاربری را وارد کنید.',
            ...$this->authMobileRequiredMessages(),
            ...$this->authPasswordRequiredMessages(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function authEmailRequiredMessages(): array
    {
        return [
            'email.required' => 'ایمیل را وارد کنید.',
            'email.email' => 'ایمیل واردشده معتبر نیست.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function authOtpCodeRequiredMessages(): array
    {
        return [
            'code.required' => 'کد تأیید را وارد کنید.',
        ];
    }
}
