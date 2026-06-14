<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'submission_url' => ['nullable', 'url', 'max:500'],
            'file_path' => ['nullable', 'string', 'max:500', 'not_regex:/^[A-Za-z]:\\\\/'],
            'attachment' => [
                'nullable',
                'file',
                'max:'.(int) config('exercise_submissions.attachment_max_kb', 5120),
                'mimes:'.implode(',', $allowedExtensions),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $submissionUrl = trim((string) $this->input('submission_url', ''));
                $filePath = trim((string) $this->input('file_path', ''));
                $hasUploadedFile = $this->file('attachment') !== null;

                if ($submissionUrl === '' && $filePath === '' && ! $hasUploadedFile) {
                    $validator->errors()->add(
                        'submission_url',
                        'حداقل یکی از لینک تمرین، فایل آپلودی یا مسیر فایل را وارد کن.',
                    );
                }
            },
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
            'attachment' => 'فایل تمرین',
        ];
    }
}
