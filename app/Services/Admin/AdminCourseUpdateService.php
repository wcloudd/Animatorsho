<?php

namespace App\Services\Admin;

use App\Enums\CourseUpdateStatus;
use App\Enums\CourseUpdateType;
use App\Enums\CourseUpdateVisualTheme;
use App\Models\CourseUpdate;
use App\Support\CourseUpdateLabels;
use App\Support\JalaliDateFormatter;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class AdminCourseUpdateService
{
    /**
     * @return array{
     *     updates: LengthAwarePaginator<int, array{
     *         id: int,
     *         title: string,
     *         summary: ?string,
     *         type: string,
     *         typeLabel: string,
     *         typeTone: string,
     *         visualTheme: string,
     *         visualThemeLabel: string,
     *         visualThemeTone: string,
     *         status: string,
     *         statusLabel: string,
     *         statusTone: string,
     *         isPinned: bool,
     *         displayOrder: int,
     *         publishedAt: ?string,
     *         publishedAtLabel: string,
     *         editUrl: string
     *     }>
     * }
     */
    public function listForAdmin(): array
    {
        $updates = CourseUpdate::query()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (CourseUpdate $update): array => $this->toListItem($update));

        return [
            'updates' => $updates,
        ];
    }

    /**
     * @return array{
     *     formOptions: array{
     *         typeOptions: list<array{value: string, label: string}>,
     *         visualThemeOptions: list<array{value: string, label: string}>,
     *         statusOptions: list<array{value: string, label: string}>
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
     *     update: array{
     *         id: int,
     *         title: string,
     *         summary: string,
     *         body: string,
     *         type: string,
     *         visualTheme: string,
     *         status: string,
     *         isPinned: bool,
     *         displayOrder: int,
     *         publishedAt: ?string
     *     },
     *     formOptions: array{
     *         typeOptions: list<array{value: string, label: string}>,
     *         visualThemeOptions: list<array{value: string, label: string}>,
     *         statusOptions: list<array{value: string, label: string}>
     *     }
     * }
     */
    public function editFormProps(CourseUpdate $courseUpdate): array
    {
        return [
            'update' => $this->toFormItem($courseUpdate),
            'formOptions' => $this->formOptions(),
        ];
    }

    /**
     * @param  array{
     *     title: string,
     *     summary?: string|null,
     *     body?: string|null,
     *     type: string,
     *     visual_theme: string,
     *     status: string,
     *     is_pinned: bool,
     *     display_order: int,
     *     published_at?: string|null
     * }  $data
     */
    public function create(array $data): CourseUpdate
    {
        $attributes = $this->attributesFromValidated($data);

        return CourseUpdate::query()->create($attributes);
    }

    /**
     * @param  array{
     *     title: string,
     *     summary?: string|null,
     *     body?: string|null,
     *     type: string,
     *     visual_theme: string,
     *     status: string,
     *     is_pinned: bool,
     *     display_order: int,
     *     published_at?: string|null
     * }  $data
     */
    public function update(CourseUpdate $courseUpdate, array $data): CourseUpdate
    {
        $courseUpdate->update($this->attributesFromValidated($data));

        return $courseUpdate->fresh();
    }

    /**
     * @param  array{
     *     title: string,
     *     summary?: string|null,
     *     body?: string|null,
     *     type: string,
     *     visual_theme: string,
     *     status: string,
     *     is_pinned: bool,
     *     display_order: int,
     *     published_at?: string|null
     * }  $data
     * @return array<string, mixed>
     */
    private function attributesFromValidated(array $data): array
    {
        $status = CourseUpdateStatus::from($data['status']);
        $publishedAt = $this->resolvePublishedAt($status, $data['published_at'] ?? null);

        return [
            'title' => $data['title'],
            'summary' => $this->nullableString($data['summary'] ?? null),
            'body' => $this->nullableString($data['body'] ?? null),
            'type' => CourseUpdateType::from($data['type']),
            'visual_theme' => CourseUpdateVisualTheme::from($data['visual_theme']),
            'status' => $status,
            'is_pinned' => $data['is_pinned'],
            'display_order' => $data['display_order'],
            'published_at' => $publishedAt,
        ];
    }

    private function resolvePublishedAt(CourseUpdateStatus $status, ?string $publishedAt): ?CarbonInterface
    {
        if ($status !== CourseUpdateStatus::Published) {
            return null;
        }

        $timezone = config('app.timezone');

        if (is_string($publishedAt) && trim($publishedAt) !== '') {
            // Admin form sends local business datetime without timezone (YYYY-MM-DDTHH:mm).
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
     *     visualThemeOptions: list<array{value: string, label: string}>,
     *     statusOptions: list<array{value: string, label: string}>
     * }
     */
    private function formOptions(): array
    {
        return [
            'typeOptions' => CourseUpdateLabels::typeOptions(),
            'visualThemeOptions' => CourseUpdateLabels::visualThemeOptions(),
            'statusOptions' => CourseUpdateLabels::statusOptions(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     summary: ?string,
     *     type: string,
     *     typeLabel: string,
     *     typeTone: string,
     *     visualTheme: string,
     *     visualThemeLabel: string,
     *     visualThemeTone: string,
     *     status: string,
     *     statusLabel: string,
     *     statusTone: string,
     *     isPinned: bool,
     *     displayOrder: int,
     *     publishedAt: ?string,
     *     publishedAtLabel: string,
     *     editUrl: string
     * }
     */
    private function toListItem(CourseUpdate $update): array
    {
        $isScheduled = $this->isScheduled($update);

        return [
            'id' => $update->id,
            'title' => $update->title,
            'summary' => $update->summary,
            'type' => $update->type->value,
            'typeLabel' => CourseUpdateLabels::type($update->type),
            'typeTone' => CourseUpdateLabels::typeTone($update->type),
            'visualTheme' => $update->visual_theme->value,
            'visualThemeLabel' => CourseUpdateLabels::visualTheme($update->visual_theme),
            'visualThemeTone' => CourseUpdateLabels::visualThemeTone($update->visual_theme),
            'status' => $update->status->value,
            'statusLabel' => $isScheduled
                ? 'زمان‌بندی‌شده'
                : CourseUpdateLabels::status($update->status),
            'statusTone' => $isScheduled
                ? 'warning'
                : CourseUpdateLabels::statusTone($update->status),
            'isScheduled' => $isScheduled,
            'isPinned' => $update->is_pinned,
            'displayOrder' => $update->display_order,
            'publishedAt' => $update->published_at?->toIso8601String(),
            'publishedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($update->published_at),
            'editUrl' => route('admin.course-updates.edit', $update),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     summary: string,
     *     body: string,
     *     type: string,
     *     visualTheme: string,
     *     status: string,
     *     isPinned: bool,
     *     displayOrder: int,
     *     publishedAt: ?string
     * }
     */
    private function toFormItem(CourseUpdate $update): array
    {
        return [
            'id' => $update->id,
            'title' => $update->title,
            'summary' => $update->summary ?? '',
            'body' => $update->body ?? '',
            'type' => $update->type->value,
            'visualTheme' => $update->visual_theme->value,
            'status' => $update->status->value,
            'isPinned' => $update->is_pinned,
            'displayOrder' => $update->display_order,
            'publishedAt' => $update->published_at
                ? $update->published_at->timezone(config('app.timezone'))->format('Y-m-d\TH:i')
                : null,
        ];
    }

    private function isScheduled(CourseUpdate $update): bool
    {
        return $update->status === CourseUpdateStatus::Published
            && $update->published_at !== null
            && $update->published_at->gt(Carbon::now(config('app.timezone')));
    }
}
