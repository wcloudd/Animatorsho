<?php

namespace App\Http\Requests\Admin;

use App\Services\StudentMedalService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentMedalAwardRequest extends FormRequest
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
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'medal_key' => ['required', 'string', Rule::in(array_keys(StudentMedalService::MEDALS))],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'هنرجو',
            'medal_key' => 'مدال',
            'note' => 'یادداشت',
        ];
    }
}
