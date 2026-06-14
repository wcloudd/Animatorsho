<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExerciseSubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminExerciseSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(ExerciseSubmissionStatus::class)],
            'admin_feedback' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'وضعیت',
            'admin_feedback' => 'بازخورد استاد',
        ];
    }
}
