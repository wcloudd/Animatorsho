<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use App\Support\IranianMobile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var User $user */
            $user = $this->user();
            $mobile = $this->input('mobile');

            if (! filled($user->mobile) || ! is_string($mobile)) {
                return;
            }

            $normalizedMobile = IranianMobile::normalize($mobile);

            if ($normalizedMobile !== null && $normalizedMobile !== $user->mobile) {
                $validator->errors()->add(
                    'mobile',
                    'تغییر شماره موبایل از این بخش امکان‌پذیر نیست. با پشتیبانی تماس بگیرید.',
                );
            }
        });
    }
}
