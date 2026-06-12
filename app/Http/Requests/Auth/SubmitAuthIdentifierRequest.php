<?php

namespace App\Http\Requests\Auth;

use App\Concerns\ProvidesAuthValidationMessages;
use App\Support\AuthIdentifier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitAuthIdentifierRequest extends FormRequest
{
    use ProvidesAuthValidationMessages;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('identifier') && is_string($this->input('identifier'))) {
            $this->merge([
                'identifier' => trim($this->input('identifier')),
            ]);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'identifier' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || AuthIdentifier::parse($value) === null) {
                        $fail(AuthIdentifier::validationMessage(is_string($value) ? $value : null));
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->authIdentifierRequiredMessages();
    }
}
