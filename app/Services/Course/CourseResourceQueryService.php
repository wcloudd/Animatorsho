<?php

namespace App\Services\Course;

class CourseResourceQueryService
{
    private const int HOME_PREVIEW_LIMIT = 3;

    public function __construct(
        private readonly CourseResourceLibraryCatalog $libraryCatalog,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function latestPublishedForHome(): array
    {
        return $this->libraryCatalog->latestPublishedForHome(self::HOME_PREVIEW_LIMIT);
    }

    /**
     * @return array{
     *     categories: list<array{id: string, label: string}>,
     *     sections: list<array{
     *         id: string,
     *         title: string,
     *         layout: string,
     *         resources: list<array<string, mixed>>
     *     }>,
     *     totalCount: int
     * }
     */
    public function publishedGroupedForIndex(): array
    {
        return $this->libraryCatalog->publishedGroupedForIndex();
    }
}
