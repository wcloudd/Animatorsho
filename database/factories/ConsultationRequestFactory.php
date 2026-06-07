<?php

namespace Database\Factories;

use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultationRequest>
 */
class ConsultationRequestFactory extends Factory
{
    protected $model = ConsultationRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->name(),
            'mobile' => fake()->numerify('09#########'),
            'note' => fake()->optional()->sentence(),
            'level' => fake()->randomElement(['beginner', 'some-design', 'made-animation', 'unsure']),
            'interest' => fake()->randomElement(['full-course', 'chapter', 'installment', 'summer-class', 'advice-only']),
            'age' => fake()->optional()->numerify('##'),
            'status' => ConsultationRequestStatus::New,
            'admin_note' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'name' => $user->name,
        ]);
    }

    public function withStatus(ConsultationRequestStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
