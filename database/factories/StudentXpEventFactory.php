<?php

namespace Database\Factories;

use App\Models\StudentXpEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentXpEvent>
 */
class StudentXpEventFactory extends Factory
{
    protected $model = StudentXpEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_type' => 'exercise_submission',
            'source_id' => null,
            'points' => 150,
            'reason' => 'تمرین تأیید شده',
            'awarded_by' => null,
            'awarded_at' => now(),
        ];
    }
}
