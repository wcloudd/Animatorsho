<?php

namespace Database\Factories;

use App\Models\ExerciseSubmission;
use App\Models\ExerciseSubmissionAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExerciseSubmissionAttachment>
 */
class ExerciseSubmissionAttachmentFactory extends Factory
{
    protected $model = ExerciseSubmissionAttachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submission = ExerciseSubmission::factory()->create();

        return [
            'exercise_submission_id' => $submission->id,
            'disk' => 'local',
            'path' => 'exercise-submissions/'.$submission->user_id.'/'.$submission->id.'/sample.pdf',
            'original_name' => 'sample.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'deleted_at' => null,
            'deleted_by' => null,
        ];
    }

    public function forSubmission(ExerciseSubmission $submission): static
    {
        return $this->state(fn () => [
            'exercise_submission_id' => $submission->id,
            'path' => 'exercise-submissions/'.$submission->user_id.'/'.$submission->id.'/sample.pdf',
        ]);
    }
}
