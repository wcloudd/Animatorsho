<?php

namespace App\Services\Course;

use Illuminate\Support\Str;

class ExerciseSubmissionPresentation
{
    public static function publicSubmissionLink(?string $submissionUrl, ?string $filePath): ?string
    {
        if ($submissionUrl !== null && $submissionUrl !== '') {
            return $submissionUrl;
        }

        if ($filePath !== null && $filePath !== '' && Str::startsWith($filePath, ['http://', 'https://'])) {
            return $filePath;
        }

        return null;
    }

    public static function submissionLinkLabel(
        ?string $submissionUrl,
        ?string $filePath,
        bool $hasUploadedAttachment = false,
    ): ?string {
        if (self::publicSubmissionLink($submissionUrl, $filePath) !== null) {
            return 'مشاهده ارسال';
        }

        if ($hasUploadedAttachment) {
            return 'دانلود فایل تمرین';
        }

        if ($filePath !== null && $filePath !== '') {
            return 'فایل ثبت‌شده';
        }

        return null;
    }
}
