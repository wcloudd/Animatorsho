<?php

namespace App\Support\StudentPanel;

use App\Enums\CourseResourceLibraryCategory;
use App\Models\CourseResourceCategory;
use Illuminate\Support\Collection;

class CourseResourcePredefinedCategories
{
    public static function ensureSynced(): void
    {
        foreach (CourseResourceLibraryCategory::cases() as $category) {
            CourseResourceCategory::query()->updateOrCreate(
                ['slug' => $category->value],
                [
                    'title' => $category->label(),
                    'description' => null,
                    'display_order' => $category->displayOrder(),
                    'is_active' => true,
                ],
            );
        }
    }

    public static function idFor(CourseResourceLibraryCategory $category): int
    {
        self::ensureSynced();

        return CourseResourceCategory::query()
            ->where('slug', $category->value)
            ->value('id');
    }

    /**
     * @return Collection<string, CourseResourceCategory>
     */
    public static function keyedBySlug(): Collection
    {
        self::ensureSynced();

        return CourseResourceCategory::query()
            ->whereIn('slug', array_map(
                fn (CourseResourceLibraryCategory $category): string => $category->value,
                CourseResourceLibraryCategory::cases(),
            ))
            ->get()
            ->keyBy('slug');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function selectOptions(): array
    {
        return array_map(
            fn (CourseResourceLibraryCategory $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
            ],
            CourseResourceLibraryCategory::cases(),
        );
    }
}
