<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function storedPasswordRules(): array
    {
        return ['required', 'string', Password::default()];
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'current_password'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function currentPasswordRulesForUser(User $user): array
    {
        if ($user->hasPassword()) {
            return $this->currentPasswordRules();
        }

        return ['nullable', 'prohibited'];
    }
}
