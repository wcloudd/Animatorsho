<?php

use App\Enums\CoursePackageType;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CoursePackage;
use Database\Seeders\AnimatorshoCourseSeeder;

test('animatorsho course seeder creates published course and five packages', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $course = Course::query()->where('slug', 'animatorsho')->first();

    expect($course)->not->toBeNull()
        ->and($course->title)->toBe('انیماتورشو')
        ->and($course->status)->toBe(CourseStatus::Published)
        ->and(CoursePackage::query()->count())->toBe(5);
});

test('animatorsho course seeder sets expected slugs types and prices', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $full = CoursePackage::query()->where('slug', 'full')->first();
    $chapterOne = CoursePackage::query()->where('slug', 'chapter-1')->first();
    $chapterTwo = CoursePackage::query()->where('slug', 'chapter-2')->first();
    $chapterThree = CoursePackage::query()->where('slug', 'chapter-3')->first();
    $chapterFour = CoursePackage::query()->where('slug', 'chapter-4')->first();

    expect($full->type)->toBe(CoursePackageType::FullCourse)
        ->and($full->price_toman)->toBe(5_500_000)
        ->and($full->chapter_number)->toBeNull()
        ->and($chapterOne->title)->toBe('فصل اول: فتو انیمیشن')
        ->and($chapterOne->price_toman)->toBe(1_500_000)
        ->and($chapterOne->chapter_number)->toBe(1)
        ->and($chapterTwo->price_toman)->toBe(1_750_000)
        ->and($chapterThree->price_toman)->toBe(1_750_000)
        ->and($chapterFour->price_toman)->toBe(1_500_000)
        ->and($chapterOne->type)->toBe(CoursePackageType::Chapter)
        ->and($full->is_active)->toBeTrue()
        ->and($chapterFour->display_order)->toBe(4);
});

test('animatorsho course seeder is idempotent', function () {
    $this->seed(AnimatorshoCourseSeeder::class);
    $this->seed(AnimatorshoCourseSeeder::class);

    expect(Course::query()->count())->toBe(1)
        ->and(CoursePackage::query()->count())->toBe(5);
});
