<?php

namespace App\Support;

use App\Enums\CourseUpdateStatus;
use App\Enums\CourseUpdateType;
use App\Enums\CourseUpdateVisualTheme;
use Carbon\CarbonInterface;

class CourseUpdateLabels
{
    public static function type(CourseUpdateType $type): string
    {
        return match ($type) {
            CourseUpdateType::Announcement => 'اطلاعیه',
            CourseUpdateType::LessonUpdate => 'به‌روزرسانی دوره',
            CourseUpdateType::ExerciseUpdate => 'تمرین',
            CourseUpdateType::ResourceAdded => 'منبع جدید',
            CourseUpdateType::Important => 'مهم',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'|'danger'
     */
    public static function typeTone(CourseUpdateType $type): string
    {
        return match ($type) {
            CourseUpdateType::Announcement => 'neutral',
            CourseUpdateType::LessonUpdate => 'neutral',
            CourseUpdateType::ExerciseUpdate => 'warning',
            CourseUpdateType::ResourceAdded => 'success',
            CourseUpdateType::Important => 'warning',
        };
    }

    public static function visualTheme(CourseUpdateVisualTheme $theme): string
    {
        return match ($theme) {
            CourseUpdateVisualTheme::Default => 'معمولی',
            CourseUpdateVisualTheme::Purple => 'بنفش',
            CourseUpdateVisualTheme::Gold => 'طلایی',
            CourseUpdateVisualTheme::Yellow => 'توجه / زرد',
            CourseUpdateVisualTheme::Blue => 'آموزشی / آبی',
            CourseUpdateVisualTheme::Green => 'تکمیل / سبز',
            CourseUpdateVisualTheme::Rainbow => 'آپدیت بزرگ / رنگین‌کمانی',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'|'danger'
     */
    public static function visualThemeTone(CourseUpdateVisualTheme $theme): string
    {
        return match ($theme) {
            CourseUpdateVisualTheme::Default => 'neutral',
            CourseUpdateVisualTheme::Purple => 'neutral',
            CourseUpdateVisualTheme::Gold => 'warning',
            CourseUpdateVisualTheme::Yellow => 'warning',
            CourseUpdateVisualTheme::Blue => 'neutral',
            CourseUpdateVisualTheme::Green => 'success',
            CourseUpdateVisualTheme::Rainbow => 'warning',
        };
    }

    public static function status(CourseUpdateStatus $status): string
    {
        return match ($status) {
            CourseUpdateStatus::Draft => 'پیش‌نویس',
            CourseUpdateStatus::Published => 'منتشرشده',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'|'danger'
     */
    public static function statusTone(CourseUpdateStatus $status): string
    {
        return match ($status) {
            CourseUpdateStatus::Draft => 'neutral',
            CourseUpdateStatus::Published => 'success',
        };
    }

    public static function publishedAtLabel(?CarbonInterface $publishedAt): string
    {
        if ($publishedAt === null) {
            return '—';
        }

        return $publishedAt
            ->timezone(config('app.timezone'))
            ->locale('fa')
            ->translatedFormat('j F Y');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function typeOptions(): array
    {
        return array_map(
            fn (CourseUpdateType $type): array => [
                'value' => $type->value,
                'label' => self::type($type),
            ],
            CourseUpdateType::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function visualThemeOptions(): array
    {
        return array_map(
            fn (CourseUpdateVisualTheme $theme): array => [
                'value' => $theme->value,
                'label' => self::visualTheme($theme),
            ],
            CourseUpdateVisualTheme::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function statusOptions(): array
    {
        return array_map(
            fn (CourseUpdateStatus $status): array => [
                'value' => $status->value,
                'label' => self::status($status),
            ],
            CourseUpdateStatus::cases(),
        );
    }
}
