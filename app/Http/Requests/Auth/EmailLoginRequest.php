<?php

namespace App\Http\Requests\Auth;

use App\Concerns\ProvidesAuthValidationMessages;
use Illuminate\Foundation\Http\FormRequest;

class EmailLoginRequest extends FormRequest
{
    use ProvidesAuthValidationMessages;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower((string) $this->input('email')),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->authEmailRequiredMessages(),
            ...$this->authPasswordRequiredMessages(),
        ];
    }
}
