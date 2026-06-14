<?php

namespace App\Services\Course;

use App\Enums\ExerciseSubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\User;
use App\Support\SafeStoryTextFormatter;
use Illuminate\Http\UploadedFile;
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
     *     attachment?: ?UploadedFile
     * }  $data
     */
    public function storeForUser(User $user, array $data): ExerciseSubmission
    {
        $submission = ExerciseSubmission::query()->create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => SafeStoryTextFormatter::sanitize($data['description'] ?? null),
            'submission_url' => $data['submission_url'] ?? null,
            'file_path' => $data['file_path'] ?? null,
            'status' => ExerciseSubmissionStatus::Submitted,
        ]);

        if (($data['attachment'] ?? null) instanceof UploadedFile) {
            try {
                $this->attachments->store($submission, $data['attachment']);
            } catch (InvalidArgumentException $exception) {
                $submission->delete();

                throw $exception;
            }
        }

        return $submission->fresh();
    }
}
