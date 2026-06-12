<?php

namespace App\Http\Requests\Admin;

use App\Support\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;

class ManualEnrollmentUserSuggestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! isset($this->q) || ! is_string($this->q)) {
            return;
        }

        $trimmed = trim($this->q);

        if ($trimmed !== '' && ! IranianMobile::looksLikeMobileAttempt($trimmed)) {
            $trimmed = strtolower($trimmed);
        }

        $this->merge([
            'q' => $trimmed === '' ? null : $trimmed,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:64'],
        ];
    }
}
