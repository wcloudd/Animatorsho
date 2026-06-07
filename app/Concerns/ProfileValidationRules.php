<?php

namespace App\Concerns;

use App\Models\User;
use App\Support\AvatarPresetRegistry;
use App\Support\IranianMobile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            'avatar_preset' => $this->avatarPresetRules(),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:80'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'nullable',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function avatarPresetRules(): array
    {
        return ['nullable', 'string', AvatarPresetRegistry::validationRule()];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function registrationMobileRules(?int $userId = null): array
    {
        return [
            'mobile' => $this->mobileRules($userId),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function mobileRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! IranianMobile::isValid(is_string($value) ? $value : null)) {
                    $fail('شماره موبایل معتبر وارد کنید (مثال: 09123456789).');
                }
            },
            $userId === null
                ? Rule::unique(User::class, 'mobile')
                : Rule::unique(User::class, 'mobile')->ignore($userId),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function normalizedMobileFromInput(array $input): ?string
    {
        $mobile = $input['mobile'] ?? null;

        if (! is_string($mobile)) {
            return null;
        }

        return IranianMobile::normalize($mobile);
    }
}
