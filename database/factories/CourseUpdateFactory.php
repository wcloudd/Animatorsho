<?php

namespace Database\Factories;

use App\Enums\CourseUpdateStatus;
use App\Enums\CourseUpdateType;
use App\Enums\CourseUpdateVisualTheme;
use App\Models\CourseUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseUpdate>
 */
class CourseUpdateFactory extends Factory
{
    protected $model = CourseUpdate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'summary' => fake()->paragraph(),
            'body' => null,
            'type' => CourseUpdateType::Announcement,
            'visual_theme' => CourseUpdateVisualTheme::Default,
            'status' => CourseUpdateStatus::Draft,
            'is_pinned' => false,
            'display_order' => 0,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => CourseUpdateStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => CourseUpdateStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (): array => [
            'is_pinned' => true,
        ]);
    }
}
