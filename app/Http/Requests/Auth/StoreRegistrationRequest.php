<?php

namespace App\Http\Requests\Auth;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Concerns\ProvidesAuthValidationMessages;
use App\Concerns\UsernameValidationRules;
use App\Support\IranianMobile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreRegistrationRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules, ProvidesAuthValidationMessages, UsernameValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        $sessionMobile = $this->session()->get('auth.pending_mobile');

        if (
            (! $this->has('mobile') || ! is_string($this->input('mobile')) || trim($this->input('mobile')) === '')
            && is_string($sessionMobile)
            && $sessionMobile !== ''
        ) {
            $merged['mobile'] = $sessionMobile;
        }

        if ($this->has('mobile') && is_string($this->input('mobile'))) {
            $normalized = IranianMobile::normalize($this->input('mobile'));

            if ($normalized !== null) {
                $merged['mobile'] = $normalized;
            }
        }

        if ($this->has('email') && is_string($this->input('email'))) {
            $email = trim($this->input('email'));
            $merged['email'] = $email !== '' ? strtolower($email) : null;
        }

        if ($this->has('username') && is_string($this->input('username'))) {
            $merged['username'] = strtolower(trim($this->input('username')));
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            ...$this->registrationUsernameRules(),
            ...$this->registrationMobileRules(),
            'password' => ['required', 'string', $this->passwordRule()],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    protected function passwordRule(): Password|string|null
    {
        $rule = Password::default();

        return $rule instanceof Password ? $rule : 'min:8';
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->authRegistrationRequiredMessages();
    }
}
