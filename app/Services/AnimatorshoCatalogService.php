<?php

namespace App\Services;

use App\Enums\CoursePackageType;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Support\TomanFormatter;
use Illuminate\Support\Collection;

class AnimatorshoCatalogService
{
    public const COURSE_SLUG = 'animatorsho';

    public const FULL_PACKAGE_SLUG = 'full';

    /**
     * @return array{
     *     fullPackage: array{slug: string, title: string, priceToman: int, chapterNumber: null},
     *     chapterPackages: list<array{slug: string, title: string, priceToman: int, chapterNumber: int}>
     * }|null
     */
    public function catalogForInertia(): ?array
    {
        $course = $this->findPublishedCourse();

        if ($course === null) {
            return null;
        }

        $packages = $this->activePackages($course);

        $full = $packages->firstWhere('slug', self::FULL_PACKAGE_SLUG);

        if ($full === null) {
            return null;
        }

        $chapters = $packages
            ->where('type', CoursePackageType::Chapter)
            ->values();

        return [
            'fullPackage' => $this->mapPackage($full),
            'chapterPackages' => $chapters
                ->map(fn (CoursePackage $package): array => $this->mapPackage($package))
                ->all(),
        ];
    }

    public function findPublishedCourse(): ?Course
    {
        return Course::query()
            ->where('slug', self::COURSE_SLUG)
            ->where('status', CourseStatus::Published)
            ->first();
    }

    /**
     * @return Collection<int, CoursePackage>
     */
    public function activePackages(?Course $course = null): Collection
    {
        $course ??= $this->findPublishedCourse();

        if ($course === null) {
            return collect();
        }

        return $course->packages()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    public function findActivePackageBySlug(string $slug): ?CoursePackage
    {
        return $this->activePackages()
            ->firstWhere('slug', $slug);
    }

    /**
     * @return array{slug: string, title: string, priceToman: int, chapterNumber: int|null}
     */
    public function mapPackage(CoursePackage $package): array
    {
        return [
            'slug' => $package->slug,
            'title' => $package->title,
            'priceToman' => $package->price_toman,
            'chapterNumber' => $package->chapter_number,
        ];
    }

    public function formatPrice(int $amount): string
    {
        return TomanFormatter::format($amount);
    }
}
