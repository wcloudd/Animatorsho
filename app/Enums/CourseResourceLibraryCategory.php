<?php

namespace App\Enums;

enum CourseResourceLibraryCategory: string
{
    case References = 'references';
    case PracticeFiles = 'practice_files';
    case Videos = 'videos';
    case ExternalLinks = 'external_links';

    public function label(): string
    {
        return match ($this) {
            self::References => 'رفرنس‌ها',
            self::PracticeFiles => 'فایل‌های تمرین و پروژه',
            self::Videos => 'ویدئو و گیف',
            self::ExternalLinks => 'لینک‌های بیرونی',
        };
    }

    public function layout(): string
    {
        return match ($this) {
            self::References, self::Videos => 'masonry',
            self::PracticeFiles, self::ExternalLinks => 'list',
        };
    }

    public function displayOrder(): int
    {
        return match ($this) {
            self::References => 1,
            self::PracticeFiles => 2,
            self::Videos => 3,
            self::ExternalLinks => 4,
        };
    }
}
