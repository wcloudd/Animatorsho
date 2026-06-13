<?php

namespace App\Support\StudentPanel;

use App\Enums\CourseResourceLibraryCategory;
use App\Enums\CourseResourceType;
use Illuminate\Support\Str;

class StudentPanelResourceScanner
{
    public const string BASE_RELATIVE_PATH = 'media/student-panel/library';

    public const string PUBLIC_URL_PREFIX = '/media/student-panel/library';

    /**
     * @var array<string, array{
     *     category: CourseResourceLibraryCategory,
     *     extensions: list<string>
     * }>
     */
    private const FOLDER_DEFINITIONS = [
        'references' => [
            'category' => CourseResourceLibraryCategory::References,
            'extensions' => ['png', 'jpg', 'jpeg', 'webp', 'gif'],
        ],
        'practice-files' => [
            'category' => CourseResourceLibraryCategory::PracticeFiles,
            'extensions' => ['zip', 'rar', 'pdf', 'fla', 'aep', 'psd', 'ai', 'blend', 'txt'],
        ],
        'videos' => [
            'category' => CourseResourceLibraryCategory::Videos,
            'extensions' => ['mp4', 'webm', 'gif'],
        ],
    ];

    /**
     * @return list<array{
     *     publicUrl: string,
     *     filename: string,
     *     extension: string,
     *     libraryCategory: string,
     *     type: string,
     *     fallbackTitle: string,
     *     modifiedAtTimestamp: int
     * }>
     */
    public function scan(): array
    {
        $results = [];

        foreach (self::FOLDER_DEFINITIONS as $folder => $definition) {
            $absolutePath = public_path(self::BASE_RELATIVE_PATH.'/'.$folder);

            if (! is_dir($absolutePath)) {
                continue;
            }

            $entries = scandir($absolutePath);

            if ($entries === false) {
                continue;
            }

            foreach ($entries as $entry) {
                if ($this->shouldIgnoreEntry($entry)) {
                    continue;
                }

                $absoluteFile = $absolutePath.DIRECTORY_SEPARATOR.$entry;

                if (! is_file($absoluteFile)) {
                    continue;
                }

                $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

                if (! in_array($extension, $definition['extensions'], true)) {
                    continue;
                }

                $results[] = [
                    'publicUrl' => self::PUBLIC_URL_PREFIX.'/'.$folder.'/'.$entry,
                    'filename' => $entry,
                    'extension' => $extension,
                    'libraryCategory' => $definition['category']->value,
                    'type' => $this->resolveType($definition['category'], $extension)->value,
                    'fallbackTitle' => $this->titleFromFilename($entry),
                    'modifiedAtTimestamp' => (int) filemtime($absoluteFile),
                ];
            }
        }

        return $results;
    }

    public static function normalizePublicPath(?string $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $trimmed = trim($path);

        if ($trimmed === '') {
            return null;
        }

        $normalized = Str::of($trimmed)
            ->replace('\\', '/')
            ->trim('/')
            ->toString();

        return '/'.$normalized;
    }

    public static function isLibraryPath(?string $path): bool
    {
        $normalized = self::normalizePublicPath($path);

        if ($normalized === null) {
            return false;
        }

        return str_starts_with($normalized, self::PUBLIC_URL_PREFIX.'/');
    }

    private function shouldIgnoreEntry(string $entry): bool
    {
        if ($entry === '.' || $entry === '..') {
            return true;
        }

        if (str_starts_with($entry, '.')) {
            return true;
        }

        $lower = strtolower($entry);

        return in_array($lower, ['readme', 'readme.md', 'readme.txt', 'thumbs.db', '.ds_store'], true);
    }

    private function resolveType(CourseResourceLibraryCategory $category, string $extension): CourseResourceType
    {
        return match ($category) {
            CourseResourceLibraryCategory::References => CourseResourceType::Image,
            CourseResourceLibraryCategory::Videos => match ($extension) {
                'gif' => CourseResourceType::Image,
                default => CourseResourceType::File,
            },
            CourseResourceLibraryCategory::PracticeFiles => match ($extension) {
                'pdf' => CourseResourceType::Pdf,
                'zip', 'rar', 'fla', 'aep', 'psd', 'ai', 'blend' => CourseResourceType::ProjectFile,
                default => CourseResourceType::File,
            },
            CourseResourceLibraryCategory::ExternalLinks => CourseResourceType::ExternalLink,
        };
    }

    private function titleFromFilename(string $filename): string
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $cleaned = str_replace(['-', '_'], ' ', $basename);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned) ?? $basename;

        return trim($cleaned) === '' ? $filename : trim($cleaned);
    }
}
