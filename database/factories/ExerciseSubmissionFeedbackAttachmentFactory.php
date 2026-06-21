<?php

namespace Database\Factories;

use App\Models\ExerciseSubmission;
use App\Models\ExerciseSubmissionFeedbackAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExerciseSubmissionFeedbackAttachment>
 */
class ExerciseSubmissionFeedbackAttachmentFactory extends Factory
{
    protected $model = ExerciseSubmissionFeedbackAttachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submission = ExerciseSubmission::factory()->create();

        return [
            'exercise_submission_id' => $submission->id,
            'uploaded_by' => null,
            'disk' => 'local',
            'path' => 'exercise-submission-feedback/'.$submission->id.'/sample.pdf',
            'original_name' => 'feedback.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
            'deleted_at' => null,
            'deleted_by' => null,
        ];
    }

    public function forSubmission(ExerciseSubmission $submission): static
    {
        return $this->state(fn () => [
            'exercise_submission_id' => $submission->id,
            'path' => 'exercise-submission-feedback/'.$submission->id.'/'.fake()->uuid().'.pdf',
        ]);
    }
}
