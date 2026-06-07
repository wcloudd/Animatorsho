<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use App\Support\IranianMobile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendProfileMobileOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
        /** @var User $user */
        $user = $this->user();

        return [
            'mobile' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! IranianMobile::isValid(is_string($value) ? $value : null)) {
                        $fail('شماره موبایل معتبر وارد کنید (مثال: 09123456789).');
                    }
                },
                Rule::unique(User::class, 'mobile')->ignore($user->id),
            ],
        ];
    }
}
