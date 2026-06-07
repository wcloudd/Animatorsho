<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCoursePackageRequest extends FormRequest
{
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
            'title' => ['required', 'string', 'max:255'],
            'price_toman' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'display_order' => ['required', 'integer', 'min:0'],
            'spotplayer_course_ids_input' => ['nullable', 'string', 'max:2000', $this->validateCourseIds(...)],
        ];
    }

    private function validateCourseIds(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $parts = preg_split('/[\s,]+/', trim($value)) ?: [];

        foreach ($parts as $part) {
            $id = trim($part);

            if ($id === '') {
                continue;
            }

            if (! preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
                $fail('شناسه دوره SpotPlayer نامعتبر است.');
            }
        }
    }
}
