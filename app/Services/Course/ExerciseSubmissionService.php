<?php

namespace App\Services\Course;

use App\Enums\ExerciseSubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\User;
use App\Support\SafeStoryTextFormatter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExerciseSubmissionService
{
    public function __construct(
        private readonly ExerciseSubmissionAttachmentStorageService $attachments,
    ) {}

    /**
     * @param  array{
     *     title: string,
     *     description?: ?string,
     *     submission_url?: ?string,
     *     file_path?: ?string,
     *     attachments?: list<UploadedFile>
     * }  $data
     */
    public function storeForUser(User $user, array $data): ExerciseSubmission
    {
        $uploadedFiles = array_values(array_filter(
            $data['attachments'] ?? [],
            fn (mixed $file): bool => $file instanceof UploadedFile,
        ));

        if ($uploadedFiles === []) {
            throw new InvalidArgumentException('حداقل یک فایل تمرین لازم است.');
        }

        try {
            return DB::transaction(function () use ($user, $data, $uploadedFiles): ExerciseSubmission {
                $submission = ExerciseSubmission::query()->create([
                    'user_id' => $user->id,
                    'title' => $data['title'],
                    'description' => SafeStoryTextFormatter::sanitize($data['description'] ?? null),
                    'submission_url' => $data['submission_url'] ?? null,
                    'file_path' => $data['file_path'] ?? null,
                    'status' => ExerciseSubmissionStatus::Submitted,
                ]);

                $this->attachments->storeMany($submission, $uploadedFiles);

                return $submission->fresh(['attachments']);
            });
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('ذخیره فایل‌های تمرین انجام نشد.', 0, $exception);
        }
    }
}
