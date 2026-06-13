<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourseResourceLibraryCategory;
use App\Enums\CourseResourceStatus;
use App\Enums\CourseResourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCourseResourceRequest extends FormRequest
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = $this->input('status');
            $type = $this->input('type');

            if ($status !== CourseResourceStatus::Published->value) {
                return;
            }

            if ($type === CourseResourceType::ExternalLink->value) {
                if (! is_string($this->input('external_url')) || trim($this->input('external_url')) === '') {
                    $validator->errors()->add('external_url', 'برای انتشار لینک بیرونی، آدرس لینک الزامی است.');
                }

                return;
            }

            if (! is_string($this->input('file_path')) || trim($this->input('file_path')) === '') {
                $validator->errors()->add('file_path', 'برای انتشار این نوع منبع، مسیر فایل الزامی است.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(CourseResourceType::class)],
            'file_path' => ['nullable', 'string', 'max:500'],
            'external_url' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(CourseResourceStatus::class)],
            'library_category' => ['required', Rule::enum(CourseResourceLibraryCategory::class)],
            'display_order' => ['required', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
