<?php

namespace Database\Factories;

use App\Models\StudentMedalAward;
use App\Models\User;
use App\Services\StudentMedalService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentMedalAward>
 */
class StudentMedalAwardFactory extends Factory
{
    protected $model = StudentMedalAward::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'medal_key' => array_key_first(StudentMedalService::MEDALS),
            'awarded_by' => null,
            'awarded_at' => now(),
            'note' => null,
            'meta' => null,
        ];
    }
}
