<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait UsernameValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function registrationUsernameRules(?int $userId = null): array
    {
        return [
            'username' => $this->usernameRules($userId, required: true),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function usernameFormatRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'min:3',
            'max:32',
            'regex:/^[a-z0-9_]+$/',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || $value === '') {
                    return;
                }

                if (str_starts_with($value, '_') || str_ends_with($value, '_')) {
                    $fail('نام کاربری نمی‌تواند با خط زیر شروع یا تمام شود.');
                }

                if (str_contains($value, '__')) {
                    $fail('نام کاربری نمی‌تواند خط زیر متوالی داشته باشد.');
                }

                $reserved = config('username.reserved', []);

                if (is_array($reserved) && in_array($value, $reserved, true)) {
                    $fail('این نام کاربری قابل استفاده نیست.');
                }
            },
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function usernameRules(?int $userId = null, bool $required = false): array
    {
        return [
            ...$this->usernameFormatRules($required),
            $userId === null
                ? Rule::unique(User::class, 'username')
                : Rule::unique(User::class, 'username')->ignore($userId),
        ];
    }
}
