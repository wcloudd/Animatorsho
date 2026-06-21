<?php

namespace App\Support;

use App\Enums\ExerciseSubmissionStatus;

class ExerciseSubmissionStatusLabels
{
    public static function status(ExerciseSubmissionStatus $status): string
    {
        return match ($status) {
            ExerciseSubmissionStatus::Submitted => 'ارسال‌شده',
            ExerciseSubmissionStatus::Reviewing => 'در حال بررسی',
            ExerciseSubmissionStatus::NeedsRevision => 'نیاز به اصلاح',
            ExerciseSubmissionStatus::Approved => 'تأیید شده',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'|'danger'
     */
    public static function statusTone(ExerciseSubmissionStatus $status): string
    {
        return match ($status) {
            ExerciseSubmissionStatus::Approved => 'success',
            ExerciseSubmissionStatus::NeedsRevision => 'danger',
            ExerciseSubmissionStatus::Submitted,
            ExerciseSubmissionStatus::Reviewing => 'warning',
        };
    }

    /**
     * @return 'success'|'warning'|'neutral'
     */
    public static function studentStatusTone(ExerciseSubmissionStatus $status): string
    {
        return match ($status) {
            ExerciseSubmissionStatus::Approved => 'success',
            ExerciseSubmissionStatus::NeedsRevision => 'warning',
            ExerciseSubmissionStatus::Submitted,
            ExerciseSubmissionStatus::Reviewing => 'warning',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function statusOptions(): array
    {
        return array_map(
            fn (ExerciseSubmissionStatus $status) => [
                'value' => $status->value,
                'label' => self::status($status),
            ],
            ExerciseSubmissionStatus::cases(),
        );
    }
}
