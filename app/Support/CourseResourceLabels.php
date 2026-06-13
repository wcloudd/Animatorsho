<?php

namespace App\Support;

use App\Enums\CourseResourceAccessScope;
use App\Enums\CourseResourceStatus;
use App\Enums\CourseResourceType;

class CourseResourceLabels
{
    public static function type(CourseResourceType $type): string
    {
        return match ($type) {
            CourseResourceType::Pdf => 'PDF',
            CourseResourceType::File => 'فایل تمرین',
            CourseResourceType::Image => 'تصویر/رفرنس',
            CourseResourceType::ProjectFile => 'فایل پروژه',
            CourseResourceType::ExternalLink => 'لینک بیرونی',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'|'danger'
     */
    public static function typeTone(CourseResourceType $type): string
    {
        return match ($type) {
            CourseResourceType::Pdf => 'neutral',
            CourseResourceType::File => 'neutral',
            CourseResourceType::Image => 'success',
            CourseResourceType::ProjectFile => 'warning',
            CourseResourceType::ExternalLink => 'neutral',
        };
    }

    public static function status(CourseResourceStatus $status): string
    {
        return match ($status) {
            CourseResourceStatus::Draft => 'پیش‌نویس',
            CourseResourceStatus::Published => 'منتشر شده',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'|'danger'
     */
    public static function statusTone(CourseResourceStatus $status): string
    {
        return match ($status) {
            CourseResourceStatus::Draft => 'neutral',
            CourseResourceStatus::Published => 'success',
        };
    }

    public static function accessScope(CourseResourceAccessScope $scope): string
    {
        return match ($scope) {
            CourseResourceAccessScope::AllStudents => 'همه هنرجوها',
            CourseResourceAccessScope::PackageSpecific => 'بسته خاص',
        };
    }

    public static function actionLabel(CourseResourceType $type): string
    {
        return $type === CourseResourceType::ExternalLink ? 'مشاهده' : 'دانلود';
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function typeOptions(): array
    {
        return array_map(
            fn (CourseResourceType $type): array => [
                'value' => $type->value,
                'label' => self::type($type),
            ],
            CourseResourceType::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function statusOptions(): array
    {
        return array_map(
            fn (CourseResourceStatus $status): array => [
                'value' => $status->value,
                'label' => self::status($status),
            ],
            CourseResourceStatus::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function accessScopeOptions(): array
    {
        return array_map(
            fn (CourseResourceAccessScope $scope): array => [
                'value' => $scope->value,
                'label' => self::accessScope($scope),
            ],
            CourseResourceAccessScope::cases(),
        );
    }
}
