<?php

namespace App\Services\Admin;

use App\Enums\CourseResourceAccessScope;
use App\Enums\CourseResourceLibraryCategory;
use App\Enums\CourseResourceStatus;
use App\Enums\CourseResourceType;
use App\Models\CourseResource;
use App\Support\CourseResourceLabels;
use App\Support\JalaliDateFormatter;
use App\Support\StudentPanel\CourseResourcePredefinedCategories;
use App\Support\StudentPanel\StudentPanelResourceScanner;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class AdminCourseResourceService
{
    public function __construct(
        private readonly StudentPanelResourceScanner $scanner,
    ) {}

    /**
     * @return array{
     *     resources: LengthAwarePaginator<int, array<string, mixed>>,
     *     detectedFiles: list<array<string, mixed>>
     * }
     */
    public function listForAdmin(): array
    {
        $resources = CourseResource::query()
            ->with('category')
            ->orderBy('display_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (CourseResource $resource): array => $this->toListItem($resource));

        return [
            'resources' => $resources,
            'detectedFiles' => $this->unmanagedDetectedFiles(),
        ];
    }

    /**
     * @return array{
     *     formOptions: array{
     *         typeOptions: list<array{value: string, label: string}>,
     *         statusOptions: list<array{value: string, label: string}>,
     *         libraryCategoryOptions: list<array{value: string, label: string}>,
     *         detectedFileOptions: list<array{value: string, label: string}>
     *     },
     *     defaults?: array{filePath?: string, libraryCategory?: string, title?: string}
     * }
     */
    public function createFormProps(): array
    {
        return [
            'formOptions' => $this->formOptions(),
            'defaults' => $this->createDefaultsFromRequest(),
        ];
    }

    /**
     * @return array{
     *     resource: array<string, mixed>,
     *     formOptions: array{
     *         typeOptions: list<array{value: string, label: string}>,
     *         statusOptions: list<array{value: string, label: string}>,
     *         libraryCategoryOptions: list<array{value: string, label: string}>,
     *         detectedFileOptions: list<array{value: string, label: string}>
     *     }
     * }
     */
    public function editFormProps(CourseResource $courseResource): array
    {
        return [
            'resource' => $this->toFormItem($courseResource),
            'formOptions' => $this->formOptions(),
        ];
    }

    /**
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     type: string,
     *     file_path?: string|null,
     *     external_url?: string|null,
     *     status: string,
     *     library_category: string,
     *     display_order: int,
     *     published_at?: string|null
     * }  $data
     */
    public function create(array $data): CourseResource
    {
        $attributes = $this->attributesFromValidated($data);

        return CourseResource::query()->create($attributes);
    }

    /**
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     type: string,
     *     file_path?: string|null,
     *     external_url?: string|null,
     *     status: string,
     *     library_category: string,
     *     display_order: int,
     *     published_at?: string|null
     * }  $data
     */
    public function update(CourseResource $courseResource, array $data): CourseResource
    {
        $courseResource->update($this->attributesFromValidated($data));

        return $courseResource->fresh();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function unmanagedDetectedFiles(): array
    {
        $managedPaths = CourseResource::query()
            ->whereNotNull('file_path')
            ->pluck('file_path')
            ->map(fn (?string $path): ?string => StudentPanelResourceScanner::normalizePublicPath($path))
            ->filter()
            ->all();

        return collect($this->scanner->scan())
            ->reject(function (array $file) use ($managedPaths): bool {
                $publicUrl = StudentPanelResourceScanner::normalizePublicPath($file['publicUrl']);

                return $publicUrl !== null && in_array($publicUrl, $managedPaths, true);
            })
            ->map(fn (array $file): array => [
                'title' => $file['fallbackTitle'],
                'filePath' => $file['publicUrl'],
                'libraryCategory' => $file['libraryCategory'],
                'libraryCategoryLabel' => CourseResourceLibraryCategory::from($file['libraryCategory'])->label(),
                'typeLabel' => CourseResourceLabels::type(CourseResourceType::from($file['type'])),
                'createUrl' => route('admin.course-resources.create', [
                    'file_path' => $file['publicUrl'],
                    'library_category' => $file['libraryCategory'],
                    'title' => $file['fallbackTitle'],
                ]),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{filePath?: string, libraryCategory?: string, title?: string}
     */
    private function createDefaultsFromRequest(): array
    {
        $defaults = [];

        $filePath = request()->string('file_path')->trim()->toString();
        $libraryCategory = request()->string('library_category')->trim()->toString();
        $title = request()->string('title')->trim()->toString();

        if ($filePath !== '') {
            $defaults['filePath'] = $filePath;
        }

        if ($libraryCategory !== '') {
            $defaults['libraryCategory'] = $libraryCategory;
        }

        if ($title !== '') {
            $defaults['title'] = $title;
        }

        return $defaults;
    }

    /**
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     type: string,
     *     file_path?: string|null,
     *     external_url?: string|null,
     *     status: string,
     *     library_category: string,
     *     display_order: int,
     *     published_at?: string|null
     * }  $data
     * @return array<string, mixed>
     */
    private function attributesFromValidated(array $data): array
    {
        $status = CourseResourceStatus::from($data['status']);
        $type = CourseResourceType::from($data['type']);
        $libraryCategory = CourseResourceLibraryCategory::from($data['library_category']);
        $publishedAt = $this->resolvePublishedAt($status, $data['published_at'] ?? null);

        return [
            'title' => $data['title'],
            'description' => $this->nullableString($data['description'] ?? null),
            'type' => $type,
            'file_path' => $type === CourseResourceType::ExternalLink
                ? null
                : $this->nullableString($data['file_path'] ?? null),
            'external_url' => $type === CourseResourceType::ExternalLink
                ? $this->nullableString($data['external_url'] ?? null)
                : null,
            'status' => $status,
            'access_scope' => CourseResourceAccessScope::AllStudents,
            'course_package_id' => null,
            'course_resource_category_id' => CourseResourcePredefinedCategories::idFor($libraryCategory),
            'display_order' => $data['display_order'],
            'published_at' => $publishedAt,
        ];
    }

    private function resolvePublishedAt(CourseResourceStatus $status, ?string $publishedAt): ?CarbonInterface
    {
        if ($status !== CourseResourceStatus::Published) {
            return null;
        }

        $timezone = config('app.timezone');

        if (is_string($publishedAt) && trim($publishedAt) !== '') {
            return Carbon::parse($publishedAt, $timezone);
        }

        return Carbon::now($timezone);
    }

    private function nullableString(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array{
     *     typeOptions: list<array{value: string, label: string}>,
     *     statusOptions: list<array{value: string, label: string}>,
     *     libraryCategoryOptions: list<array{value: string, label: string}>,
     *     detectedFileOptions: list<array{value: string, label: string}>
     * }
     */
    private function formOptions(): array
    {
        return [
            'typeOptions' => CourseResourceLabels::typeOptions(),
            'statusOptions' => CourseResourceLabels::statusOptions(),
            'libraryCategoryOptions' => CourseResourcePredefinedCategories::selectOptions(),
            'detectedFileOptions' => collect($this->scanner->scan())
                ->map(fn (array $file): array => [
                    'value' => $file['publicUrl'],
                    'label' => $file['fallbackTitle'].' — '.$file['publicUrl'],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(CourseResource $resource): array
    {
        $isScheduled = $this->isScheduled($resource);
        $libraryCategory = $this->resolveLibraryCategoryForResource($resource);

        return [
            'id' => $resource->id,
            'title' => $resource->title,
            'description' => $resource->description,
            'type' => $resource->type->value,
            'typeLabel' => CourseResourceLabels::type($resource->type),
            'typeTone' => CourseResourceLabels::typeTone($resource->type),
            'status' => $resource->status->value,
            'statusLabel' => $isScheduled
                ? 'زمان‌بندی‌شده'
                : CourseResourceLabels::status($resource->status),
            'statusTone' => $isScheduled
                ? 'warning'
                : CourseResourceLabels::statusTone($resource->status),
            'categoryLabel' => $libraryCategory->label(),
            'sourceLabel' => 'مدیریت‌شده',
            'displayOrder' => $resource->display_order,
            'publishedAt' => $resource->published_at?->toIso8601String(),
            'publishedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($resource->published_at),
            'isScheduled' => $isScheduled,
            'editUrl' => route('admin.course-resources.edit', $resource),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toFormItem(CourseResource $resource): array
    {
        return [
            'id' => $resource->id,
            'title' => $resource->title,
            'description' => $resource->description ?? '',
            'type' => $resource->type->value,
            'filePath' => $resource->file_path ?? '',
            'externalUrl' => $resource->external_url ?? '',
            'status' => $resource->status->value,
            'libraryCategory' => $this->resolveLibraryCategoryForResource($resource)->value,
            'displayOrder' => $resource->display_order,
            'publishedAt' => $resource->published_at
                ? $resource->published_at->timezone(config('app.timezone'))->format('Y-m-d\TH:i')
                : null,
        ];
    }

    private function resolveLibraryCategoryForResource(CourseResource $resource): CourseResourceLibraryCategory
    {
        if ($resource->type === CourseResourceType::ExternalLink) {
            return CourseResourceLibraryCategory::ExternalLinks;
        }

        if ($resource->category?->slug !== null) {
            foreach (CourseResourceLibraryCategory::cases() as $category) {
                if ($category->value === $resource->category->slug) {
                    return $category;
                }
            }
        }

        return match ($resource->type) {
            CourseResourceType::Image => CourseResourceLibraryCategory::References,
            default => CourseResourceLibraryCategory::PracticeFiles,
        };
    }

    private function isScheduled(CourseResource $resource): bool
    {
        return $resource->status === CourseResourceStatus::Published
            && $resource->published_at !== null
            && $resource->published_at->gt(Carbon::now(config('app.timezone')));
    }
}
