<?php

use App\Support\StudentPanel\StudentPanelMedia;

test('publicUrlIfExists returns null when file is missing', function () {
    expect(StudentPanelMedia::publicUrlIfExists('media/student-panel/.pest-missing-file.png'))
        ->toBeNull();
});

test('publicUrlIfExists returns relative public url when file exists', function () {
    $relativePath = 'media/student-panel/.pest-existing-file.png';
    $fullPath = public_path($relativePath);

    if (! is_dir(dirname($fullPath))) {
        mkdir(dirname($fullPath), 0777, true);
    }

    file_put_contents($fullPath, 'test');

    try {
        expect(StudentPanelMedia::publicUrlIfExists($relativePath))
            ->toBe('/media/student-panel/.pest-existing-file.png');
    } finally {
        @unlink($fullPath);
    }
});

test('resolveUrl prefers configured url over auto detection', function () {
    expect(StudentPanelMedia::resolveUrl('https://cdn.example.com/guide.mp4', 'start-guide.mp4'))
        ->toBe('https://cdn.example.com/guide.mp4');
});

test('resolveUrl auto detects local file when configured url is empty', function () {
    $relativePath = 'media/student-panel/.pest-auto-guide.pdf';
    $fullPath = public_path($relativePath);

    if (! is_dir(dirname($fullPath))) {
        mkdir(dirname($fullPath), 0777, true);
    }

    file_put_contents($fullPath, 'pdf');

    try {
        expect(StudentPanelMedia::resolveUrl(null, '.pest-auto-guide.pdf'))
            ->toBe('/media/student-panel/.pest-auto-guide.pdf');
    } finally {
        @unlink($fullPath);
    }
});

test('resolvedOnboarding keeps null media urls when files are missing', function () {
    withoutDefaultStudentPanelMediaFiles(function () {
        $onboarding = StudentPanelMedia::resolvedOnboarding();

        expect($onboarding['imageUrl'])->toBeNull()
            ->and($onboarding['videoUrl'])->toBeNull()
            ->and($onboarding['videoPosterUrl'])->toBeNull()
            ->and($onboarding['pdfUrl'])->toBeNull()
            ->and($onboarding['videoTitle'])->toBe('ویدئو راهنمای پنل هنرجو');
    });
});

test('resolvedSectionVisuals keeps null image urls when files are missing', function () {
    withoutDefaultStudentPanelMediaFiles(function () {
        $sectionVisuals = StudentPanelMedia::resolvedSectionVisuals();

        expect($sectionVisuals['exercises']['imageUrl'])->toBeNull()
            ->and($sectionVisuals['mentor']['imageUrl'])->toBeNull()
            ->and($sectionVisuals['resources']['imageUrl'])->toBeNull()
            ->and($sectionVisuals['medals']['imageUrl'])->toBeNull()
            ->and($sectionVisuals['updates']['imageUrl'])->toBeNull()
            ->and($sectionVisuals['exercises']['placeholderTitle'])->toBe('تصویر تمرین');
    });
});

test('readme in student panel media directory does not auto activate section visuals', function () {
    withoutDefaultStudentPanelMediaFiles(function () {
        expect(is_file(public_path('media/student-panel/README.md')))->toBeTrue();

        $sectionVisuals = StudentPanelMedia::resolvedSectionVisuals();

        expect($sectionVisuals['exercises']['imageUrl'])->toBeNull()
            ->and(StudentPanelMedia::resolveUrl(null, 'exercises-header.png'))->toBeNull();
    });
});

test('resolvedOnboarding auto activates video url when start guide file exists', function () {
    withoutDefaultStudentPanelMediaFiles(function () {
        $relativePath = 'media/student-panel/start-guide.mp4';
        $fullPath = public_path($relativePath);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }

        file_put_contents($fullPath, 'video');

        try {
            $onboarding = StudentPanelMedia::resolvedOnboarding();

            expect($onboarding['videoUrl'])->toBe('/media/student-panel/start-guide.mp4');
        } finally {
            @unlink($fullPath);
        }
    });
});
