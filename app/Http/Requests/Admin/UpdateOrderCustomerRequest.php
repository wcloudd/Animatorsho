<?php

namespace App\Http\Requests\Admin;

use App\Concerns\CustomerValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderCustomerRequest extends FormRequest
{
    use CustomerValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizedCustomerInput($this->all()));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->customerInfoRules();
    }
}
