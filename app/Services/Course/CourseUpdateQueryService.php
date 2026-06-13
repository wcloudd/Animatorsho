<?php

namespace App\Services\Course;

use App\Enums\CourseUpdateStatus;
use App\Models\CourseUpdate;
use App\Support\CourseUpdateLabels;
use App\Support\JalaliDateFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CourseUpdateQueryService
{
    private const int HOME_PREVIEW_LIMIT = 3;

    /**
     * @return list<array{
     *     id: string,
     *     title: string,
     *     summary: string,
     *     type: string,
     *     typeLabel: string,
     *     visualTheme: string,
     *     visualThemeLabel: string,
     *     publishedAt: ?string,
     *     publishedAtLabel: string,
     *     isPinned: bool,
     *     body: ?string,
     *     imageUrl: null,
     *     imageAlt: null
     * }>
     */
    public function latestPublishedForHome(): array
    {
        $now = Carbon::now(config('app.timezone'));

        return CourseUpdate::query()
            ->where('status', CourseUpdateStatus::Published)
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', $now);
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->limit(self::HOME_PREVIEW_LIMIT)
            ->get()
            ->map(fn (CourseUpdate $update): array => $this->toHomePreviewItem($update))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     summary: string,
     *     type: string,
     *     typeLabel: string,
     *     visualTheme: string,
     *     visualThemeLabel: string,
     *     publishedAt: ?string,
     *     publishedAtLabel: string,
     *     isPinned: bool,
     *     body: ?string,
     *     imageUrl: null,
     *     imageAlt: null
     * }
     */
    private function toHomePreviewItem(CourseUpdate $update): array
    {
        return [
            'id' => (string) $update->id,
            'title' => $update->title,
            'summary' => $update->summary ?? '',
            'type' => $update->type->value,
            'typeLabel' => CourseUpdateLabels::type($update->type),
            'visualTheme' => $update->visual_theme->value,
            'visualThemeLabel' => CourseUpdateLabels::visualTheme($update->visual_theme),
            'publishedAt' => $update->published_at?->toIso8601String(),
            'publishedAtLabel' => JalaliDateFormatter::publishedAtLabel($update->published_at),
            'isPinned' => $update->is_pinned,
            'body' => $this->nullableBody($update->body),
            'imageUrl' => null,
            'imageAlt' => null,
        ];
    }

    private function nullableBody(?string $body): ?string
    {
        if (! is_string($body)) {
            return null;
        }

        $trimmed = trim($body);

        return $trimmed === '' ? null : $trimmed;
    }
}
