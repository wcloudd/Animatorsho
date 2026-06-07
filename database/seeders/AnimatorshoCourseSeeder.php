<?php

namespace Database\Seeders;

use App\Enums\CoursePackageType;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CoursePackage;
use Illuminate\Database\Seeder;

class AnimatorshoCourseSeeder extends Seeder
{
    /**
     * Seed the Animatorsho course catalog with initial editable package prices.
     */
    public function run(): void
    {
        $course = Course::query()->updateOrCreate(
            ['slug' => 'animatorsho'],
            [
                'title' => 'انیماتورشو',
                'description' => null,
                'status' => CourseStatus::Published,
            ],
        );

        $packages = [
            [
                'slug' => 'full',
                'title' => 'دوره جامع انیماتورشو',
                'type' => CoursePackageType::FullCourse,
                'chapter_number' => null,
                'price_toman' => 5_500_000,
                'display_order' => 0,
            ],
            [
                'slug' => 'chapter-1',
                'title' => 'فصل اول: فتو انیمیشن',
                'type' => CoursePackageType::Chapter,
                'chapter_number' => 1,
                'price_toman' => 1_500_000,
                'display_order' => 1,
            ],
            [
                'slug' => 'chapter-2',
                'title' => 'فصل دوم: طراحی کاراکتر',
                'type' => CoursePackageType::Chapter,
                'chapter_number' => 2,
                'price_toman' => 1_750_000,
                'display_order' => 2,
            ],
            [
                'slug' => 'chapter-3',
                'title' => 'فصل سوم: ادوب انیمیت',
                'type' => CoursePackageType::Chapter,
                'chapter_number' => 3,
                'price_toman' => 1_750_000,
                'display_order' => 3,
            ],
            [
                'slug' => 'chapter-4',
                'title' => 'فصل چهارم: انیمیشن با گوشی',
                'type' => CoursePackageType::Chapter,
                'chapter_number' => 4,
                'price_toman' => 1_500_000,
                'display_order' => 4,
            ],
        ];

        foreach ($packages as $package) {
            CoursePackage::query()->updateOrCreate(
                ['slug' => $package['slug']],
                [
                    'course_id' => $course->id,
                    'title' => $package['title'],
                    'type' => $package['type'],
                    'chapter_number' => $package['chapter_number'],
                    'description' => null,
                    'price_toman' => $package['price_toman'],
                    'is_active' => true,
                    'display_order' => $package['display_order'],
                ],
            );
        }
    }
}
