<?php

namespace App\Http\Requests\Admin;

use App\Concerns\UsernameValidationRules;
use App\Support\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;

class ManualEnrollmentLookupRequest extends FormRequest
{
    use UsernameValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merged = $this->all();

        if (isset($merged['user_lookup']) && is_string($merged['user_lookup'])) {
            $trimmedLookup = trim($merged['user_lookup']);

            if ($trimmedLookup !== '' && ! IranianMobile::looksLikeMobileAttempt($trimmedLookup)) {
                $trimmedLookup = strtolower($trimmedLookup);
            }

            $merged['user_lookup'] = $trimmedLookup === '' ? null : $trimmedLookup;
        }

        if (isset($merged['customer_mobile']) && is_string($merged['customer_mobile'])) {
            $normalized = IranianMobile::normalize(trim($merged['customer_mobile']));

            $merged['customer_mobile'] = $normalized;
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_lookup' => ['nullable', 'string', 'max:64'],
            'customer_mobile' => ['nullable', 'string', 'max:32'],
        ];
    }
}
