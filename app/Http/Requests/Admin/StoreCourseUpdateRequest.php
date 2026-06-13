<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourseUpdateStatus;
use App\Enums\CourseUpdateType;
use App\Enums\CourseUpdateVisualTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseUpdateRequest extends FormRequest
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
        return $this->sharedRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', Rule::enum(CourseUpdateType::class)],
            'visual_theme' => ['required', Rule::enum(CourseUpdateVisualTheme::class)],
            'status' => ['required', Rule::enum(CourseUpdateStatus::class)],
            'is_pinned' => ['required', 'boolean'],
            'display_order' => ['required', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
