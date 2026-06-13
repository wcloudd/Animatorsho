<?php

namespace App\Services\Admin;

use App\Enums\CourseResourceAccessScope;
use App\Enums\CourseResourceStatus;
use App\Enums\CourseResourceType;
use App\Models\CourseResource;
use App\Models\CourseResourceCategory;
use App\Support\CourseResourceLabels;
use App\Support\JalaliDateFormatter;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class AdminCourseResourceService
{
    /**
     * @return array{
     *     resources: LengthAwarePaginator<int, array{
     *         id: int,
     *         title: string,
     *         description: ?string,
     *         type: string,
     *         typeLabel: string,
     *         typeTone: string,
     *         status: string,
     *         statusLabel: string,
     *         statusTone: string,
     *         categoryLabel: string,
     *         accessScopeLabel: string,
     *         displayOrder: int,
     *         publishedAt: ?string,
     *         publishedAtLabel: string,
     *         isScheduled: bool,
     *         editUrl: string
     *     }>
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
        ];
    }

    /**
     * @return array{
     *     formOptions: array{
     *         typeOptions: list<array{value: string, label: string}>,
     *         statusOptions: list<array{value: string, label: string}>,
     *         accessScopeOptions: list<array{value: string, label: string}>,
     *         categoryOptions: list<array{value: string, label: string}>
     *     }
     * }
     */
    public function createFormProps(): array
    {
        return [
            'formOptions' => $this->formOptions(),
        ];
    }

    /**
     * @return array{
     *     resource: array{
     *         id: int,
     *         title: string,
     *         description: string,
     *         type: string,
     *         filePath: string,
     *         externalUrl: string,
     *         status: string,
     *         accessScope: string,
     *         coursePackageId: ?int,
     *         categoryId: ?int,
     *         displayOrder: int,
     *         publishedAt: ?string
     *     },
     *     formOptions: array{
     *         typeOptions: list<array{value: string, label: string}>,
     *         statusOptions: list<array{value: string, label: string}>,
     *         accessScopeOptions: list<array{value: string, label: string}>,
     *         categoryOptions: list<array{value: string, label: string}>
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
     *     access_scope: string,
     *     course_package_id?: int|null,
     *     course_resource_category_id?: int|null,
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
     *     access_scope: string,
     *     course_package_id?: int|null,
     *     course_resource_category_id?: int|null,
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
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     type: string,
     *     file_path?: string|null,
     *     external_url?: string|null,
     *     status: string,
     *     access_scope: string,
     *     course_package_id?: int|null,
     *     course_resource_category_id?: int|null,
     *     display_order: int,
     *     published_at?: string|null
     * }  $data
     * @return array<string, mixed>
     */
    private function attributesFromValidated(array $data): array
    {
        $status = CourseResourceStatus::from($data['status']);
        $type = CourseResourceType::from($data['type']);
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
            'access_scope' => CourseResourceAccessScope::from($data['access_scope']),
            'course_package_id' => $data['course_package_id'] ?? null,
            'course_resource_category_id' => $data['course_resource_category_id'] ?? null,
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
     *     accessScopeOptions: list<array{value: string, label: string}>,
     *     categoryOptions: list<array{value: string, label: string}>
     * }
     */
    private function formOptions(): array
    {
        $categoryOptions = CourseResourceCategory::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('title')
            ->get()
            ->map(fn (CourseResourceCategory $category): array => [
                'value' => (string) $category->id,
                'label' => $category->title,
            ])
            ->values()
            ->all();

        return [
            'typeOptions' => CourseResourceLabels::typeOptions(),
            'statusOptions' => CourseResourceLabels::statusOptions(),
            'accessScopeOptions' => CourseResourceLabels::accessScopeOptions(),
            'categoryOptions' => $categoryOptions,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     description: ?string,
     *     type: string,
     *     typeLabel: string,
     *     typeTone: string,
     *     status: string,
     *     statusLabel: string,
     *     statusTone: string,
     *     categoryLabel: string,
     *     accessScopeLabel: string,
     *     displayOrder: int,
     *     publishedAt: ?string,
     *     publishedAtLabel: string,
     *     isScheduled: bool,
     *     editUrl: string
     * }
     */
    private function toListItem(CourseResource $resource): array
    {
        $isScheduled = $this->isScheduled($resource);

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
            'categoryLabel' => $resource->category?->title ?? 'بدون دسته',
            'accessScopeLabel' => CourseResourceLabels::accessScope($resource->access_scope),
            'displayOrder' => $resource->display_order,
            'publishedAt' => $resource->published_at?->toIso8601String(),
            'publishedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($resource->published_at),
            'isScheduled' => $isScheduled,
            'editUrl' => route('admin.course-resources.edit', $resource),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     description: string,
     *     type: string,
     *     filePath: string,
     *     externalUrl: string,
     *     status: string,
     *     accessScope: string,
     *     coursePackageId: ?int,
     *     categoryId: ?int,
     *     displayOrder: int,
     *     publishedAt: ?string
     * }
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
            'accessScope' => $resource->access_scope->value,
            'coursePackageId' => $resource->course_package_id,
            'categoryId' => $resource->course_resource_category_id,
            'displayOrder' => $resource->display_order,
            'publishedAt' => $resource->published_at
                ? $resource->published_at->timezone(config('app.timezone'))->format('Y-m-d\TH:i')
                : null,
        ];
    }

    private function isScheduled(CourseResource $resource): bool
    {
        return $resource->status === CourseResourceStatus::Published
            && $resource->published_at !== null
            && $resource->published_at->gt(Carbon::now(config('app.timezone')));
    }
}
