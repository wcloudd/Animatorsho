<?php

namespace App\Http\Requests\Auth;

use App\Concerns\ProvidesAuthValidationMessages;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyRegistrationOtpRequest extends FormRequest
{
    use ProvidesAuthValidationMessages;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        $codeLength = (int) config('otp.code_length', 6);

        return [
            'code' => ['required', 'string', 'digits:'.$codeLength],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->authOtpCodeRequiredMessages();
    }
}
