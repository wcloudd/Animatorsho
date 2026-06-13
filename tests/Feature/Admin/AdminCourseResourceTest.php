<?php

use App\Enums\CourseResourceLibraryCategory;
use App\Enums\CourseResourceStatus;
use App\Enums\CourseResourceType;
use App\Models\CourseResource;
use App\Models\User;
use App\Support\StudentPanel\CourseResourcePredefinedCategories;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    CourseResourcePredefinedCategories::ensureSynced();
});

function placeAdminLibraryFile(string $folder, string $filename, string $content = 'test'): string
{
    $directory = public_path('media/student-panel/library/'.$folder);
    File::ensureDirectoryExists($directory);
    File::put($directory.'/'.$filename, $content);

    return '/media/student-panel/library/'.$folder.'/'.$filename;
}

afterEach(function () {
    $paths = glob(public_path('media/student-panel/library/*/*')) ?: [];

    foreach ($paths as $path) {
        if (is_file($path) && basename($path) !== '.gitkeep') {
            File::delete($path);
        }
    }
});

test('guest cannot access admin course resources', function () {
    $this->get(route('admin.course-resources.index'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access admin course resources', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.course-resources.index'))
        ->assertForbidden();
});

test('admin can view course resources index', function () {
    $admin = User::factory()->admin()->create();

    CourseResource::factory()->published()->create([
        'title' => 'منبع تستی',
        'type' => CourseResourceType::File,
        'course_resource_category_id' => CourseResourcePredefinedCategories::idFor(
            CourseResourceLibraryCategory::PracticeFiles,
        ),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.course-resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/course-resources/index')
            ->has('resources.data', 1)
            ->where('resources.data.0.title', 'منبع تستی')
            ->where('resources.data.0.typeLabel', 'فایل تمرین')
            ->where('resources.data.0.categoryLabel', 'فایل‌های تمرین و پروژه')
            ->where('resources.data.0.statusLabel', 'منتشر شده')
        );
});

test('admin index lists auto-detected files without metadata', function () {
    $admin = User::factory()->admin()->create();
    $filePath = placeAdminLibraryFile('references', 'auto-ref.png', 'png');

    $this->actingAs($admin)
        ->get(route('admin.course-resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('detectedFiles', 1)
            ->where('detectedFiles.0.filePath', $filePath)
            ->where('detectedFiles.0.libraryCategoryLabel', 'رفرنس‌ها')
        );
});

test('admin can create a draft course resource', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.course-resources.store'), [
            'title' => 'پیش‌نویس جدید',
            'description' => 'توضیح کوتاه',
            'type' => CourseResourceType::Pdf->value,
            'file_path' => '/media/student-panel/library/practice-files/draft.pdf',
            'external_url' => null,
            'status' => CourseResourceStatus::Draft->value,
            'library_category' => CourseResourceLibraryCategory::PracticeFiles->value,
            'display_order' => 2,
            'published_at' => null,
        ])
        ->assertRedirect();

    $resource = CourseResource::query()->first();

    expect($resource)->not->toBeNull()
        ->and($resource->title)->toBe('پیش‌نویس جدید')
        ->and($resource->status)->toBe(CourseResourceStatus::Draft)
        ->and($resource->published_at)->toBeNull();
});

test('admin can create a published course resource with all students access by default', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.course-resources.store'), [
            'title' => 'منبع منتشرشده',
            'description' => 'توضیح',
            'type' => CourseResourceType::ProjectFile->value,
            'file_path' => '/media/student-panel/library/practice-files/project.zip',
            'external_url' => null,
            'status' => CourseResourceStatus::Published->value,
            'library_category' => CourseResourceLibraryCategory::PracticeFiles->value,
            'display_order' => 0,
            'published_at' => null,
        ])
        ->assertRedirect();

    $resource = CourseResource::query()->first();

    expect($resource)->not->toBeNull()
        ->and($resource->status)->toBe(CourseResourceStatus::Published)
        ->and($resource->published_at)->not->toBeNull()
        ->and($resource->type)->toBe(CourseResourceType::ProjectFile)
        ->and($resource->access_scope->value)->toBe('all_students');
});

test('admin course resource validation rejects invalid type status and library category', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.course-resources.store'), [
            'title' => '',
            'description' => null,
            'type' => 'invalid-type',
            'file_path' => null,
            'external_url' => null,
            'status' => 'hidden',
            'library_category' => 'everyone',
            'display_order' => 0,
            'published_at' => null,
        ])
        ->assertSessionHasErrors(['title', 'type', 'status', 'library_category']);
});

test('admin cannot publish external link resource without external url', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.course-resources.store'), [
            'title' => 'لینک بدون آدرس',
            'description' => null,
            'type' => CourseResourceType::ExternalLink->value,
            'file_path' => null,
            'external_url' => null,
            'status' => CourseResourceStatus::Published->value,
            'library_category' => CourseResourceLibraryCategory::ExternalLinks->value,
            'display_order' => 0,
            'published_at' => null,
        ])
        ->assertSessionHasErrors(['external_url']);
});

test('admin cannot publish file resource without file path', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.course-resources.store'), [
            'title' => 'فایل بدون مسیر',
            'description' => null,
            'type' => CourseResourceType::Pdf->value,
            'file_path' => null,
            'external_url' => null,
            'status' => CourseResourceStatus::Published->value,
            'library_category' => CourseResourceLibraryCategory::PracticeFiles->value,
            'display_order' => 0,
            'published_at' => null,
        ])
        ->assertSessionHasErrors(['file_path']);
});

test('admin can store published_at with gregorian date and time', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.course-resources.store'), [
            'title' => 'منبع با تاریخ مشخص',
            'description' => null,
            'type' => CourseResourceType::Pdf->value,
            'file_path' => '/media/student-panel/library/practice-files/dated.pdf',
            'external_url' => null,
            'status' => CourseResourceStatus::Published->value,
            'library_category' => CourseResourceLibraryCategory::PracticeFiles->value,
            'display_order' => 0,
            'published_at' => '2026-06-01T14:30',
        ])
        ->assertRedirect();

    $resource = CourseResource::query()->first();

    expect($resource)->not->toBeNull()
        ->and($resource->published_at?->format('Y-m-d H:i'))->toBe('2026-06-01 14:30');
});

test('admin index shows scheduled badge for future published resources', function () {
    $admin = User::factory()->admin()->create();

    CourseResource::factory()->published()->create([
        'title' => 'منبع آینده',
        'published_at' => now()->addDay(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.course-resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('resources.data.0.statusLabel', 'زمان‌بندی‌شده')
            ->where('resources.data.0.isScheduled', true)
            ->where('resources.data.0.statusTone', 'warning')
        );
});

test('admin index exposes jalali published at label with time', function () {
    $admin = User::factory()->admin()->create();

    CourseResource::factory()->published()->create([
        'title' => 'منبع با تاریخ شمسی',
        'published_at' => '2026-06-01 14:30:00',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.course-resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('resources.data.0.publishedAtLabel', '۱۱ خرداد ۱۴۰۵، ۱۴:۳۰')
            ->where('resources.data.0.publishedAtLabel', fn (string $label): bool => ! str_contains($label, 'June')
                && ! str_contains($label, '2026'))
        );
});

test('admin create form exposes predefined library category labels', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.course-resources.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/course-resources/create')
            ->has('formOptions.libraryCategoryOptions')
            ->where('formOptions.libraryCategoryOptions', fn ($options): bool => collect($options)->contains(
                fn (array $option): bool => $option['value'] === CourseResourceLibraryCategory::References->value
                    && $option['label'] === 'رفرنس‌ها',
            ))
            ->missing('formOptions.accessScopeOptions')
        );
});
