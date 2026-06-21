<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExerciseSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowedExtensions = config('exercise_submissions.attachment_extensions', []);
        $maxAttachments = (int) config('exercise_submissions.max_attachments_per_submission', 3);
        $maxKb = (int) config('exercise_submissions.attachment_max_kb', 5120);

        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'submission_url' => ['nullable', 'url', 'max:500'],
            'file_path' => ['nullable', 'string', 'max:500', 'not_regex:/^[A-Za-z]:\\\\/'],
            'attachments' => ['required', 'array', 'min:1', 'max:'.$maxAttachments],
            'attachments.*' => [
                'required',
                'file',
                'max:'.$maxKb,
                'mimes:'.implode(',', $allowedExtensions),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'عنوان تمرین',
            'description' => 'توضیح تمرین / متن داستان',
            'submission_url' => 'لینک تمرین',
            'file_path' => 'مسیر فایل',
            'attachments' => 'فایل تمرین',
            'attachments.*' => 'فایل تمرین',
        ];
    }
}
