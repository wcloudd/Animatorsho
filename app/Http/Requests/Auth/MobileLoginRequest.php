<?php

namespace App\Http\Requests\Auth;

use App\Support\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;

class MobileLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = IranianMobile::normalize($this->input('mobile'));

        if ($normalized !== null) {
            $this->merge(['mobile' => $normalized]);
        }
    }
}
