<?php

namespace App\Concerns;

use App\Support\IranianMobile;
use Illuminate\Contracts\Validation\ValidationRule;

trait CustomerValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function customerInfoRules(): array
    {
        return [
            'customer_name' => $this->customerNameRules(),
            'customer_mobile' => $this->customerMobileRules(),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function customerNameRules(): array
    {
        return ['required', 'string', 'min:3', 'max:255'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function customerMobileRules(): array
    {
        return [
            'required',
            'string',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! IranianMobile::isValid(is_string($value) ? $value : null)) {
                    $fail('شماره موبایل معتبر وارد کنید (مثال: 09123456789).');
                }
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizedCustomerInput(array $data): array
    {
        if (isset($data['customer_name']) && is_string($data['customer_name'])) {
            $data['customer_name'] = trim($data['customer_name']);
        }

        if (isset($data['customer_mobile']) && is_string($data['customer_mobile'])) {
            $normalized = IranianMobile::normalize($data['customer_mobile']);

            if ($normalized !== null) {
                $data['customer_mobile'] = $normalized;
            }
        }

        return $data;
    }
}
