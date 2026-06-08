<?php

namespace App\Http\Requests\Auth;

use App\Support\IranianMobile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendPasswordResetOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mobile = $this->input('mobile');

        if (! is_string($mobile)) {
            return;
        }

        $normalized = IranianMobile::normalize($mobile);

        if ($normalized !== null) {
            $this->merge(['mobile' => $normalized]);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'mobile' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! IranianMobile::isValid(is_string($value) ? $value : null)) {
                        $fail('شماره موبایل معتبر وارد کنید (مثال: 09123456789).');
                    }
                },
            ],
        ];
    }
}
