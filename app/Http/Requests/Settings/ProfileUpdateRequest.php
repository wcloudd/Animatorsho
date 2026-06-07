<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id);
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $avatarPreset = $this->input('avatar_preset');

        $this->merge([
            'email' => is_string($email) && trim($email) === '' ? null : $email,
            'avatar_preset' => is_string($avatarPreset) && trim($avatarPreset) === '' ? null : $avatarPreset,
        ]);
    }
}
