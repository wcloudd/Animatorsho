<?php

use App\Support\StudentPanel\StudentPanelResourceScanner;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->libraryBase = public_path('media/student-panel/library');
    File::ensureDirectoryExists($this->libraryBase.'/references');
    File::ensureDirectoryExists($this->libraryBase.'/practice-files');
    File::ensureDirectoryExists($this->libraryBase.'/videos');
});

afterEach(function () {
    $paths = [
        public_path('media/student-panel/library/references/test-ref.png'),
        public_path('media/student-panel/library/references/README.md'),
        public_path('media/student-panel/library/references/.hidden.png'),
        public_path('media/student-panel/library/references/invalid.exe'),
        public_path('media/student-panel/library/practice-files/week-1.pdf'),
        public_path('media/student-panel/library/videos/demo.gif'),
    ];

    foreach ($paths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }
});

function placeLibraryTestFile(string $folder, string $filename, string $content = 'test'): string
{
    $directory = public_path('media/student-panel/library/'.$folder);
    File::ensureDirectoryExists($directory);
    File::put($directory.'/'.$filename, $content);

    return '/media/student-panel/library/'.$folder.'/'.$filename;
}

test('scanner returns valid files from allowed library folders', function () {
    $referenceUrl = placeLibraryTestFile('references', 'test-ref.png', 'png-content');
    $practiceUrl = placeLibraryTestFile('practice-files', 'week-1.pdf', 'pdf-content');

    $results = app(StudentPanelResourceScanner::class)->scan();
    $urls = collect($results)->pluck('publicUrl')->all();

    expect($urls)->toContain($referenceUrl)
        ->and($urls)->toContain($practiceUrl);
});

test('scanner ignores invalid extensions readme and hidden files', function () {
    placeLibraryTestFile('references', 'test-ref.png');
    placeLibraryTestFile('references', 'invalid.exe', 'binary');
    File::put(public_path('media/student-panel/library/references/README.md'), '# readme');
    File::put(public_path('media/student-panel/library/references/.hidden.png'), 'hidden');

    $results = app(StudentPanelResourceScanner::class)->scan();

    expect(collect($results)->pluck('filename')->all())->toBe(['test-ref.png']);
});

test('scanner generates cleaned fallback titles from filenames', function () {
    placeLibraryTestFile('practice-files', 'week-1-practice.pdf');

    $results = app(StudentPanelResourceScanner::class)->scan();

    expect(collect($results)->firstWhere('filename', 'week-1-practice.pdf')['fallbackTitle'])
        ->toBe('week 1 practice');
});
