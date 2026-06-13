<?php

namespace App\Services\Course;

use App\Enums\CourseResourceAccessScope;
use App\Enums\CourseResourceLibraryCategory;
use App\Enums\CourseResourceStatus;
use App\Enums\CourseResourceType;
use App\Models\CourseResource;
use App\Support\CourseResourceLabels;
use App\Support\JalaliDateFormatter;
use App\Support\StudentPanel\CourseResourcePredefinedCategories;
use App\Support\StudentPanel\StudentPanelResourceScanner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class CourseResourceLibraryCatalog
{
    public function __construct(
        private readonly StudentPanelResourceScanner $scanner,
    ) {}

    /**
     * @return Collection<int, array{
     *     id: string,
     *     source: string,
     *     libraryCategory: string,
     *     layout: string,
     *     title: string,
     *     description: string,
     *     type: string,
     *     typeLabel: string,
     *     fileExtension: ?string,
     *     categoryLabel: string,
     *     publishedAt: ?string,
     *     publishedAtLabel: string,
     *     actionUrl: ?string,
     *     actionLabel: string,
     *     isAvailable: bool,
     *     previewUrl: ?string,
     *     isVideo: bool,
     *     isGif: bool,
     *     displayOrder: int,
     *     sortTimestamp: int
     * }>
     */
    public function publishedResources(): Collection
    {
        CourseResourcePredefinedCategories::ensureSynced();

        $now = Carbon::now(config('app.timezone'));
        $scannedFiles = collect($this->scanner->scan());
        $databaseRecords = CourseResource::query()
            ->with('category')
            ->get();

        $databaseByPath = $databaseRecords
            ->filter(fn (CourseResource $resource): bool => is_string($resource->file_path) && trim($resource->file_path) !== '')
            ->keyBy(fn (CourseResource $resource): string => StudentPanelResourceScanner::normalizePublicPath($resource->file_path) ?? '');

        $resolved = collect();
        $consumedPaths = [];

        foreach ($scannedFiles as $file) {
            $publicUrl = StudentPanelResourceScanner::normalizePublicPath($file['publicUrl']) ?? $file['publicUrl'];
            $databaseRecord = $databaseByPath->get($publicUrl);

            if ($databaseRecord !== null) {
                $consumedPaths[] = $publicUrl;

                if (! $this->isDatabaseRecordVisible($databaseRecord, $now)) {
                    continue;
                }

                $resolved->push($this->fromDatabaseRecord($databaseRecord, $file));

                continue;
            }

            $resolved->push($this->fromDetectedFile($file));
        }

        foreach ($databaseRecords as $databaseRecord) {
            if ($databaseRecord->type === CourseResourceType::ExternalLink) {
                if ($this->isDatabaseRecordVisible($databaseRecord, $now)) {
                    $resolved->push($this->fromDatabaseRecord($databaseRecord));
                }

                continue;
            }

            $normalizedPath = StudentPanelResourceScanner::normalizePublicPath($databaseRecord->file_path);

            if ($normalizedPath !== null && in_array($normalizedPath, $consumedPaths, true)) {
                continue;
            }

            if ($this->isDatabaseRecordVisible($databaseRecord, $now)) {
                $resolved->push($this->fromDatabaseRecord($databaseRecord));
            }
        }

        return $this->sortResolvedResources($resolved);
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
        $resources = $this->publishedResources();

        $sections = collect(CourseResourceLibraryCategory::cases())
            ->sortBy(fn (CourseResourceLibraryCategory $category): int => $category->displayOrder())
            ->map(function (CourseResourceLibraryCategory $category) use ($resources): ?array {
                $sectionResources = $resources
                    ->where('libraryCategory', $category->value)
                    ->values()
                    ->map(fn (array $resource): array => $this->toStudentItem($resource))
                    ->all();

                if ($sectionResources === []) {
                    return null;
                }

                return [
                    'id' => $category->value,
                    'title' => $category->label(),
                    'layout' => $category->layout(),
                    'resources' => $sectionResources,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'categories' => $this->filterCategories(),
            'sections' => $sections,
            'totalCount' => $resources->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function latestPublishedForHome(int $limit = 3): array
    {
        return $this->publishedResources()
            ->take($limit)
            ->map(fn (array $resource): array => $this->toHomePreviewItem($resource))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array<string, mixed>
     */
    private function toHomePreviewItem(array $resource): array
    {
        $item = $this->toStudentItem($resource);

        if ($item['isAvailable']) {
            $item['actionLabel'] = 'مشاهده';
            $item['actionUrl'] = Route::has('course.resources.index')
                ? route('course.resources.index')
                : $item['actionUrl'];
        }

        return $item;
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    private function filterCategories(): array
    {
        $categories = [
            ['id' => 'all', 'label' => 'همه'],
        ];

        foreach (CourseResourceLibraryCategory::cases() as $category) {
            $categories[] = [
                'id' => $category->value,
                'label' => $category->label(),
            ];
        }

        return $categories;
    }

    private function isDatabaseRecordVisible(CourseResource $resource, Carbon $now): bool
    {
        if ($resource->status !== CourseResourceStatus::Published) {
            return false;
        }

        if ($resource->access_scope !== CourseResourceAccessScope::AllStudents) {
            return false;
        }

        if ($resource->published_at !== null && $resource->published_at->gt($now)) {
            return false;
        }

        if ($resource->category !== null && ! $resource->category->is_active) {
            return false;
        }

        return true;
    }

    /**
     * @param  array{
     *     publicUrl: string,
     *     filename: string,
     *     extension: string,
     *     libraryCategory: string,
     *     type: string,
     *     fallbackTitle: string,
     *     modifiedAtTimestamp: int
     * }  $file
     * @return array<string, mixed>
     */
    private function fromDetectedFile(array $file): array
    {
        $type = CourseResourceType::from($file['type']);
        $libraryCategory = CourseResourceLibraryCategory::from($file['libraryCategory']);
        $publicUrl = StudentPanelResourceScanner::normalizePublicPath($file['publicUrl']) ?? $file['publicUrl'];

        return [
            'id' => 'detected:'.sha1($publicUrl),
            'source' => 'detected',
            'libraryCategory' => $libraryCategory->value,
            'layout' => $libraryCategory->layout(),
            'title' => $file['fallbackTitle'],
            'description' => '',
            'type' => $type->value,
            'typeLabel' => CourseResourceLabels::type($type),
            'fileExtension' => strtoupper($file['extension']),
            'categoryLabel' => $libraryCategory->label(),
            'publishedAt' => null,
            'publishedAtLabel' => '—',
            'actionUrl' => $publicUrl,
            'actionLabel' => CourseResourceLabels::actionLabel($type),
            'isAvailable' => true,
            'previewUrl' => $this->previewUrlFor($type, $publicUrl, $file['extension']),
            'isVideo' => $this->isVideoExtension($file['extension']),
            'isGif' => $file['extension'] === 'gif',
            'displayOrder' => 1000,
            'sortTimestamp' => $file['modifiedAtTimestamp'],
        ];
    }

    /**
     * @param  array{
     *     publicUrl: string,
     *     filename: string,
     *     extension: string,
     *     libraryCategory: string,
     *     type: string,
     *     fallbackTitle: string,
     *     modifiedAtTimestamp: int
     * }|null  $detectedFile
     * @return array<string, mixed>
     */
    private function fromDatabaseRecord(CourseResource $resource, ?array $detectedFile = null): array
    {
        $type = $resource->type;
        $libraryCategory = $this->resolveLibraryCategory($resource, $detectedFile);
        $actionUrl = $this->resolveActionUrl($resource);
        $extension = $detectedFile['extension'] ?? $this->extensionFromPath($resource->file_path);

        return [
            'id' => 'db:'.$resource->id,
            'source' => 'database',
            'libraryCategory' => $libraryCategory->value,
            'layout' => $libraryCategory->layout(),
            'title' => $resource->title,
            'description' => $resource->description ?? '',
            'type' => $type->value,
            'typeLabel' => CourseResourceLabels::type($type),
            'fileExtension' => $extension !== null ? strtoupper($extension) : null,
            'categoryLabel' => $libraryCategory->label(),
            'publishedAt' => $resource->published_at?->toIso8601String(),
            'publishedAtLabel' => JalaliDateFormatter::publishedAtLabel($resource->published_at),
            'actionUrl' => $actionUrl,
            'actionLabel' => CourseResourceLabels::actionLabel($type),
            'isAvailable' => $actionUrl !== null,
            'previewUrl' => $this->previewUrlFor($type, $actionUrl, $extension),
            'isVideo' => $extension !== null && $this->isVideoExtension($extension),
            'isGif' => $extension === 'gif',
            'displayOrder' => $resource->display_order,
            'sortTimestamp' => $resource->published_at?->getTimestamp() ?? ($detectedFile['modifiedAtTimestamp'] ?? 0),
        ];
    }

    /**
     * @param  array{
     *     publicUrl: string,
     *     filename: string,
     *     extension: string,
     *     libraryCategory: string,
     *     type: string,
     *     fallbackTitle: string,
     *     modifiedAtTimestamp: int
     * }|null  $detectedFile
     */
    private function resolveLibraryCategory(CourseResource $resource, ?array $detectedFile): CourseResourceLibraryCategory
    {
        if ($resource->type === CourseResourceType::ExternalLink) {
            return CourseResourceLibraryCategory::ExternalLinks;
        }

        if ($detectedFile !== null) {
            return CourseResourceLibraryCategory::from($detectedFile['libraryCategory']);
        }

        $normalizedPath = StudentPanelResourceScanner::normalizePublicPath($resource->file_path);

        if ($normalizedPath !== null) {
            if (str_contains($normalizedPath, '/library/references/')) {
                return CourseResourceLibraryCategory::References;
            }

            if (str_contains($normalizedPath, '/library/practice-files/')) {
                return CourseResourceLibraryCategory::PracticeFiles;
            }

            if (str_contains($normalizedPath, '/library/videos/')) {
                return CourseResourceLibraryCategory::Videos;
            }
        }

        if ($resource->category?->slug !== null) {
            $slug = $resource->category->slug;

            foreach (CourseResourceLibraryCategory::cases() as $category) {
                if ($category->value === $slug) {
                    return $category;
                }
            }
        }

        return match ($resource->type) {
            CourseResourceType::Image => CourseResourceLibraryCategory::References,
            CourseResourceType::ExternalLink => CourseResourceLibraryCategory::ExternalLinks,
            default => CourseResourceLibraryCategory::PracticeFiles,
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $resources
     * @return Collection<int, array<string, mixed>>
     */
    private function sortResolvedResources(Collection $resources): Collection
    {
        return $resources
            ->sortBy([
                ['displayOrder', 'asc'],
                fn (array $resource): int => -$resource['sortTimestamp'],
                ['title', 'asc'],
            ])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array<string, mixed>
     */
    private function toStudentItem(array $resource): array
    {
        return [
            'id' => $resource['id'],
            'title' => $resource['title'],
            'description' => $resource['description'],
            'type' => $resource['type'],
            'typeLabel' => $resource['typeLabel'],
            'libraryCategory' => $resource['libraryCategory'],
            'layout' => $resource['layout'],
            'fileExtension' => $resource['fileExtension'],
            'categoryLabel' => $resource['categoryLabel'],
            'publishedAt' => $resource['publishedAt'],
            'publishedAtLabel' => $resource['publishedAtLabel'],
            'actionUrl' => $resource['actionUrl'],
            'actionLabel' => $resource['actionLabel'],
            'isAvailable' => $resource['isAvailable'],
            'previewUrl' => $resource['previewUrl'],
            'isVideo' => $resource['isVideo'],
            'isGif' => $resource['isGif'],
            'imageUrl' => null,
            'imageAlt' => null,
        ];
    }

    private function resolveActionUrl(CourseResource $resource): ?string
    {
        if ($resource->type === CourseResourceType::ExternalLink) {
            return $this->nullableString($resource->external_url);
        }

        return $this->nullableString($resource->file_path);
    }

    private function previewUrlFor(CourseResourceType $type, ?string $url, ?string $extension): ?string
    {
        if ($url === null) {
            return null;
        }

        if ($type === CourseResourceType::ExternalLink) {
            return null;
        }

        if ($extension !== null && ($this->isVideoExtension($extension) && $extension !== 'gif')) {
            return null;
        }

        if (in_array($type, [CourseResourceType::Image, CourseResourceType::Pdf], true)) {
            return $url;
        }

        if ($extension !== null && in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
            return $url;
        }

        return null;
    }

    private function isVideoExtension(string $extension): bool
    {
        return in_array(strtolower($extension), ['mp4', 'webm'], true);
    }

    private function extensionFromPath(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $extension === '' ? null : $extension;
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
