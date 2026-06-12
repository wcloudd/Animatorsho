<?php

namespace App\Http\Requests\Admin;

use App\Concerns\CustomerValidationRules;
use App\Concerns\UsernameValidationRules;
use App\Enums\ExternalEnrollmentSource;
use App\Support\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreManualEnrollmentRequest extends FormRequest
{
    use CustomerValidationRules;
    use UsernameValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merged = $this->normalizedCustomerInput($this->all());

        if (isset($merged['user_lookup']) && is_string($merged['user_lookup'])) {
            $trimmedLookup = trim($merged['user_lookup']);

            if ($trimmedLookup !== '' && ! IranianMobile::looksLikeMobileAttempt($trimmedLookup)) {
                $trimmedLookup = strtolower($trimmedLookup);
            }

            $merged['user_lookup'] = $trimmedLookup === '' ? null : $trimmedLookup;
        }

        if (isset($merged['admin_note']) && is_string($merged['admin_note'])) {
            $merged['admin_note'] = trim($merged['admin_note']);
        }

        if (isset($merged['license_key']) && is_string($merged['license_key'])) {
            $merged['license_key'] = trim($merged['license_key']);
        }

        if (! isset($merged['source']) || $merged['source'] === '') {
            $merged['source'] = ExternalEnrollmentSource::Eitaa->value;
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => $this->customerNameRules(),
            'user_lookup' => ['nullable', 'string', 'max:64'],
            'customer_mobile' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    if (! IranianMobile::isValid($value)) {
                        $fail(IranianMobile::validationMessage($value));
                    }
                },
            ],
            'course_package_id' => [
                'required',
                'integer',
                Rule::exists('course_packages', 'id')->where('is_active', true),
            ],
            'source' => ['required', 'string', Rule::enum(ExternalEnrollmentSource::class)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'license_key' => ['nullable', 'string', 'min:3', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $lookup = $this->input('user_lookup');
            $mobile = $this->input('customer_mobile');

            if (! is_string($lookup) || $lookup === '') {
                if (! is_string($mobile) || trim($mobile) === '') {
                    $validator->errors()->add(
                        'customer_mobile',
                        'برای کاربر جدید، شماره موبایل یا جستجوی کاربر موجود را وارد کنید.',
                    );
                }

                return;
            }

            if (IranianMobile::looksLikeMobileAttempt($lookup) || IranianMobile::normalize($lookup) !== null) {
                return;
            }

            $usernameValidator = validator(
                ['user_lookup' => $lookup],
                ['user_lookup' => $this->usernameFormatRules(required: true)],
            );

            if ($usernameValidator->fails()) {
                foreach ($usernameValidator->errors()->get('user_lookup') as $message) {
                    $validator->errors()->add('user_lookup', $message);
                }
            }
        });
    }
}
