<?php

namespace App\Services\Course;

use App\Enums\CourseResourceAccessScope;
use App\Enums\CourseResourceStatus;
use App\Enums\CourseResourceType;
use App\Models\CourseResource;
use App\Support\CourseResourceLabels;
use App\Support\JalaliDateFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CourseResourceQueryService
{
    private const int HOME_PREVIEW_LIMIT = 3;

    /**
     * @return list<array{
     *     id: string,
     *     title: string,
     *     description: string,
     *     type: string,
     *     typeLabel: string,
     *     categoryLabel: ?string,
     *     publishedAt: ?string,
     *     publishedAtLabel: string,
     *     actionUrl: ?string,
     *     actionLabel: string,
     *     isAvailable: bool,
     *     imageUrl: null,
     *     imageAlt: null
     * }>
     */
    public function latestPublishedForHome(): array
    {
        return $this->sortResources(
            $this->publishedVisibleQuery()->get(),
        )
            ->take(self::HOME_PREVIEW_LIMIT)
            ->map(fn (CourseResource $resource): array => $this->toResourceItem($resource))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     groups: list<array{
     *         id: string,
     *         title: string,
     *         resources: list<array{
     *             id: string,
     *             title: string,
     *             description: string,
     *             type: string,
     *             typeLabel: string,
     *             categoryLabel: ?string,
     *             publishedAt: ?string,
     *             publishedAtLabel: string,
     *             actionUrl: ?string,
     *             actionLabel: string,
     *             isAvailable: bool
     *         }>
     *     }>,
     *     totalCount: int
     * }
     */
    public function publishedGroupedForIndex(): array
    {
        $resources = $this->publishedVisibleQuery()->get();
        $grouped = [];
        $uncategorized = collect();

        foreach ($resources as $resource) {
            if ($resource->category === null) {
                $uncategorized->push($resource);

                continue;
            }

            $categoryId = (string) $resource->category->id;

            if (! isset($grouped[$categoryId])) {
                $grouped[$categoryId] = [
                    'id' => $categoryId,
                    'title' => $resource->category->title,
                    'displayOrder' => $resource->category->display_order,
                    'resources' => collect(),
                ];
            }

            $grouped[$categoryId]['resources']->push($resource);
        }

        $groups = collect($grouped)
            ->sortBy([
                ['displayOrder', 'asc'],
                ['title', 'asc'],
            ])
            ->values()
            ->map(function (array $group): array {
                return [
                    'id' => $group['id'],
                    'title' => $group['title'],
                    'resources' => $this->sortResources($group['resources'])
                        ->map(fn (CourseResource $resource): array => $this->toResourceItem($resource))
                        ->values()
                        ->all(),
                ];
            })
            ->all();

        if ($uncategorized->isNotEmpty()) {
            $groups[] = [
                'id' => 'uncategorized',
                'title' => 'بدون دسته‌بندی',
                'resources' => $this->sortResources($uncategorized)
                    ->map(fn (CourseResource $resource): array => $this->toResourceItem($resource))
                    ->values()
                    ->all(),
            ];
        }

        return [
            'groups' => $groups,
            'totalCount' => $resources->count(),
        ];
    }

    /**
     * @return Builder<CourseResource>
     */
    private function publishedVisibleQuery(): Builder
    {
        $now = Carbon::now(config('app.timezone'));

        return CourseResource::query()
            ->with('category')
            ->where('status', CourseResourceStatus::Published)
            ->where('access_scope', CourseResourceAccessScope::AllStudents)
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', $now);
            })
            ->where(function (Builder $query): void {
                $query->whereNull('course_resource_category_id')
                    ->orWhereHas('category', function (Builder $categoryQuery): void {
                        $categoryQuery->where('is_active', true);
                    });
            });
    }

    /**
     * @param  Collection<int, CourseResource>  $resources
     * @return Collection<int, CourseResource>
     */
    private function sortResources(Collection $resources): Collection
    {
        return $resources
            ->sortBy([
                ['display_order', 'asc'],
                fn (CourseResource $resource): int => -($resource->published_at?->getTimestamp() ?? 0),
                fn (CourseResource $resource): int => -$resource->id,
            ])
            ->values();
    }

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     description: string,
     *     type: string,
     *     typeLabel: string,
     *     categoryLabel: ?string,
     *     publishedAt: ?string,
     *     publishedAtLabel: string,
     *     actionUrl: ?string,
     *     actionLabel: string,
     *     isAvailable: bool
     * }
     */
    private function toResourceItem(CourseResource $resource): array
    {
        $actionUrl = $this->resolveActionUrl($resource);

        return [
            'id' => (string) $resource->id,
            'title' => $resource->title,
            'description' => $resource->description ?? '',
            'type' => $resource->type->value,
            'typeLabel' => CourseResourceLabels::type($resource->type),
            'categoryLabel' => $resource->category?->title,
            'publishedAt' => $resource->published_at?->toIso8601String(),
            'publishedAtLabel' => JalaliDateFormatter::publishedAtLabel($resource->published_at),
            'actionUrl' => $actionUrl,
            'actionLabel' => CourseResourceLabels::actionLabel($resource->type),
            'isAvailable' => $actionUrl !== null,
        ];
    }

    private function resolveActionUrl(CourseResource $resource): ?string
    {
        if ($resource->type === CourseResourceType::ExternalLink) {
            $url = $this->nullableString($resource->external_url);

            return $url !== null ? $url : null;
        }

        $path = $this->nullableString($resource->file_path);

        return $path !== null ? $path : null;
    }

    private function nullableString(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
