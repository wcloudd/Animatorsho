<?php

namespace Database\Factories;

use App\Enums\CourseResourceAccessScope;
use App\Enums\CourseResourceStatus;
use App\Enums\CourseResourceType;
use App\Models\CourseResource;
use App\Models\CourseResourceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseResource>
 */
class CourseResourceFactory extends Factory
{
    protected $model = CourseResource::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_resource_category_id' => CourseResourceCategory::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'type' => CourseResourceType::Pdf,
            'file_path' => '/media/student-panel/library/practice-files/example.pdf',
            'external_url' => null,
            'status' => CourseResourceStatus::Draft,
            'access_scope' => CourseResourceAccessScope::AllStudents,
            'course_package_id' => null,
            'display_order' => 0,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => CourseResourceStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => CourseResourceStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function externalLink(): static
    {
        return $this->state(fn (): array => [
            'type' => CourseResourceType::ExternalLink,
            'file_path' => null,
            'external_url' => 'https://example.com/reference',
        ]);
    }
}
