<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingsRequest extends FormRequest
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
            'purchases_enabled' => ['required', 'boolean'],
            'maintenance_mode_enabled' => ['required', 'boolean'],
            'maintenance_title' => ['nullable', 'string', 'max:120'],
            'maintenance_message' => ['nullable', 'string', 'max:1000'],
            'student_panel_show_getting_started_section' => ['required', 'boolean'],
        ];
    }
}
