<?php

namespace Database\Factories;

use App\Enums\CoursePackageType;
use App\Models\Course;
use App\Models\CoursePackage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CoursePackage>
 */
class CoursePackageFactory extends Factory
{
    protected $model = CoursePackage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'course_id' => Course::factory(),
            'title' => $title,
            'slug' => Str::slug($title.'-'.fake()->unique()->numerify('###')),
            'type' => CoursePackageType::Chapter,
            'chapter_number' => fake()->numberBetween(1, 4),
            'description' => fake()->optional()->sentence(),
            'price_toman' => fake()->numberBetween(1_000_000, 3_000_000),
            'is_active' => true,
            'display_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function fullCourse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CoursePackageType::FullCourse,
            'chapter_number' => null,
            'display_order' => 0,
        ]);
    }

    public function chapter(int $chapterNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CoursePackageType::Chapter,
            'chapter_number' => $chapterNumber,
            'slug' => 'chapter-'.$chapterNumber.'-'.fake()->unique()->numerify('###'),
            'display_order' => $chapterNumber,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
