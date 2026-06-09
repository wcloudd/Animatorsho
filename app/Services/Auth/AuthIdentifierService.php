<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Support\AuthIdentifier;
use App\Support\ParsedAuthIdentifier;
use Illuminate\Validation\ValidationException;

class AuthIdentifierService
{
    public const ACTION_MOBILE_OTP_LOGIN = 'mobile_otp_login';

    public const ACTION_REGISTRATION = 'registration';

    public const ACTION_EMAIL_LOGIN = 'email_login';

    /**
     * @return array{action: string, mobile: ?string, email: ?string}
     */
    public function resolve(ParsedAuthIdentifier $identifier): array
    {
        if ($identifier->type === ParsedAuthIdentifier::Mobile) {
            return $this->resolveMobile($identifier->value);
        }

        return $this->resolveEmail($identifier->value);
    }

    /**
     * @return array{action: string, mobile: ?string, email: ?string}
     */
    private function resolveMobile(string $mobile): array
    {
        if (User::query()->where('mobile', $mobile)->exists()) {
            return [
                'action' => self::ACTION_MOBILE_OTP_LOGIN,
                'mobile' => $mobile,
                'email' => null,
            ];
        }

        return [
            'action' => self::ACTION_REGISTRATION,
            'mobile' => $mobile,
            'email' => null,
        ];
    }

    /**
     * @return array{action: string, mobile: ?string, email: ?string}
     */
    private function resolveEmail(string $email): array
    {
        if (User::query()->where('email', $email)->exists()) {
            return [
                'action' => self::ACTION_EMAIL_LOGIN,
                'mobile' => null,
                'email' => $email,
            ];
        }

        throw ValidationException::withMessages([
            'identifier' => AuthIdentifier::UNKNOWN_EMAIL_MESSAGE,
        ]);
    }
}
