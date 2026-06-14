<?php

namespace Database\Factories;

use App\Enums\ExerciseSubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExerciseSubmission>
 */
class ExerciseSubmissionFactory extends Factory
{
    protected $model = ExerciseSubmission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'submission_url' => fake()->url(),
            'file_path' => null,
            'status' => ExerciseSubmissionStatus::Submitted,
            'admin_feedback' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function reviewing(): static
    {
        return $this->state(fn () => ['status' => ExerciseSubmissionStatus::Reviewing]);
    }

    public function needsRevision(): static
    {
        return $this->state(fn () => [
            'status' => ExerciseSubmissionStatus::NeedsRevision,
            'admin_feedback' => 'لطفاً حرکت شخصیت را روان‌تر کن.',
            'reviewed_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => ExerciseSubmissionStatus::Approved,
            'admin_feedback' => 'عالی بود! ادامه بده.',
            'reviewed_at' => now(),
        ]);
    }
}
