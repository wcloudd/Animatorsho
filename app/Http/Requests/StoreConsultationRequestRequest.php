<?php

namespace App\Http\Requests;

use App\Support\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        if ($this->has('full_name') && is_string($this->input('full_name'))) {
            $merged['name'] = trim($this->input('full_name'));
        }

        if ($this->has('mobile') && is_string($this->input('mobile'))) {
            $normalized = IranianMobile::normalize($this->input('mobile'));

            if ($normalized !== null) {
                $merged['mobile'] = $normalized;
            }
        }

        if ($this->has('note') && is_string($this->input('note'))) {
            $note = trim($this->input('note'));
            $merged['note'] = $note !== '' ? $note : null;
        }

        foreach (['level', 'interest', 'age'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $value = trim($this->input($field));
                $merged[$field] = $value !== '' ? $value : null;
            }
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'mobile' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! IranianMobile::isValid(is_string($value) ? $value : null)) {
                    $fail('شماره موبایل معتبر وارد کنید (مثال: 09123456789).');
                }
            }],
            'note' => ['nullable', 'string', 'max:2000'],
            'level' => ['nullable', 'string', 'in:beginner,some-design,made-animation,unsure'],
            'interest' => ['nullable', 'string', 'in:full-course,chapter,installment,summer-class,advice-only'],
            'age' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'نام و نام خانوادگی',
            'mobile' => 'شماره موبایل',
            'note' => 'توضیح کوتاه',
            'level' => 'سطح فعلی',
            'interest' => 'علاقه‌مند به',
            'age' => 'سن',
        ];
    }
}
