<?php

namespace App\Support\StudentPanel;

class StudentPanelMedia
{
    public static function publicUrlIfExists(string $relativePublicPath): ?string
    {
        $normalized = ltrim($relativePublicPath, '/');
        $fullPath = public_path($normalized);

        if (! is_file($fullPath)) {
            return null;
        }

        return '/'.$normalized;
    }

    public static function publicUrlFromFilename(string $filename): ?string
    {
        $basePath = (string) config('student_panel.media.basePath', 'media/student-panel');
        $basePath = trim($basePath, '/');
        $filename = ltrim($filename, '/');

        if ($filename === '') {
            return null;
        }

        return self::publicUrlIfExists($basePath.'/'.$filename);
    }

    public static function resolveUrl(?string $configuredUrl, string $filename): ?string
    {
        if (is_string($configuredUrl) && trim($configuredUrl) !== '') {
            return trim($configuredUrl);
        }

        return self::publicUrlFromFilename($filename);
    }

    /**
     * @return array<string, mixed>
     */
    public static function resolvedOnboarding(): array
    {
        /** @var array<string, mixed> $onboarding */
        $onboarding = config('student_panel.onboarding', []);

        /** @var array<string, string> $media */
        $media = config('student_panel.media', []);

        return array_merge($onboarding, [
            'imageUrl' => self::resolveUrl(
                is_string($onboarding['imageUrl'] ?? null) ? $onboarding['imageUrl'] : null,
                $media['onboardingBanner'] ?? 'onboarding-banner.png',
            ),
            'videoUrl' => self::resolveUrl(
                is_string($onboarding['videoUrl'] ?? null) ? $onboarding['videoUrl'] : null,
                $media['startGuideVideo'] ?? 'start-guide.mp4',
            ),
            'videoPosterUrl' => self::resolveUrl(
                is_string($onboarding['videoPosterUrl'] ?? null) ? $onboarding['videoPosterUrl'] : null,
                $media['startGuidePoster'] ?? 'start-guide-poster.png',
            ),
            'pdfUrl' => self::resolveUrl(
                is_string($onboarding['pdfUrl'] ?? null) ? $onboarding['pdfUrl'] : null,
                $media['startGuidePdf'] ?? 'start-guide.pdf',
            ),
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function resolvedSectionVisuals(): array
    {
        /** @var array<string, array<string, mixed>> $sectionVisuals */
        $sectionVisuals = config('student_panel.sectionVisuals', []);

        /** @var array<string, string> $media */
        $media = config('student_panel.media', []);

        $filenameKeys = [
            'exercises' => 'exercisesHeader',
            'mentor' => 'mentorHeader',
            'resources' => 'resourcesHeader',
            'medals' => 'medalsHeader',
            'updates' => 'updatesHeader',
        ];

        $resolved = [];

        foreach ($filenameKeys as $sectionKey => $mediaKey) {
            $section = $sectionVisuals[$sectionKey] ?? [];
            $defaultFilename = match ($sectionKey) {
                'exercises' => 'exercises-header.png',
                'mentor' => 'mentor-header.png',
                'resources' => 'resources-header.png',
                'medals' => 'medals-header.png',
                'updates' => 'updates-header.png',
                default => '',
            };

            $resolved[$sectionKey] = array_merge($section, [
                'imageUrl' => self::resolveUrl(
                    is_string($section['imageUrl'] ?? null) ? $section['imageUrl'] : null,
                    $media[$mediaKey] ?? $defaultFilename,
                ),
            ]);
        }

        return $resolved;
    }
}
