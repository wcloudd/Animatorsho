<?php

namespace App\Http\Requests\Admin;

use App\Concerns\CustomerValidationRules;
use App\Support\IranianMobile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSmsSettingsRequest extends FormRequest
{
    use CustomerValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'admin_notifications_enabled' => ['required', 'boolean'],
            'admin_mobile' => ['nullable', 'string', ...$this->optionalCustomerMobileRules()],
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    private function optionalCustomerMobileRules(): array
    {
        return [
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }

                if (! is_string($value)) {
                    $fail('شماره موبایل معتبر وارد کنید (مثال: 09123456789).');

                    return;
                }

                if (! IranianMobile::isValid($value)) {
                    $fail('شماره موبایل معتبر وارد کنید (مثال: 09123456789).');
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        if (isset($data['admin_mobile']) && is_string($data['admin_mobile'])) {
            $normalized = IranianMobile::normalize($data['admin_mobile']);
            $data['admin_mobile'] = $normalized;
        }

        return $data;
    }
}
