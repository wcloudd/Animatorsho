<?php

use App\Enums\CourseResourceAccessScope;
use App\Enums\CourseResourceLibraryCategory;
use App\Enums\CourseResourceType;
use App\Models\CoursePackage;
use App\Models\CourseResource;
use App\Models\CourseResourceCategory;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Support\StudentPanel\CourseResourcePredefinedCategories;
use Database\Seeders\AnimatorshoCourseSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AnimatorshoCourseSeeder::class);
    CourseResourcePredefinedCategories::ensureSynced();
});

function placeStudentLibraryFile(string $folder, string $filename, string $content = 'test'): string
{
    $directory = public_path('media/student-panel/library/'.$folder);
    File::ensureDirectoryExists($directory);
    File::put($directory.'/'.$filename, $content);

    return '/media/student-panel/library/'.$folder.'/'.$filename;
}

afterEach(function () {
    $base = public_path('media/student-panel/library');

    if (! is_dir($base)) {
        return;
    }

    foreach (['references', 'practice-files', 'videos'] as $folder) {
        $directory = $base.'/'.$folder;

        if (! is_dir($directory)) {
            continue;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === '.gitkeep') {
                continue;
            }

            $path = $directory.'/'.$entry;

            if (is_file($path)) {
                File::delete($path);
            }
        }
    }
});

test('active student sees published course resources on course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-1',
        ]);

    $filePath = placeStudentLibraryFile('practice-files', 'week-1.pdf', 'pdf');

    CourseResource::factory()->published()->create([
        'title' => 'فایل تمرین هفته اول',
        'description' => 'تمرین ساده انیمیشن',
        'type' => CourseResourceType::Pdf,
        'file_path' => $filePath,
        'course_resource_category_id' => CourseResourcePredefinedCategories::idFor(
            CourseResourceLibraryCategory::PracticeFiles,
        ),
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.resources', 1)
            ->where('preview.resources.0.title', 'فایل تمرین هفته اول')
            ->where('preview.resources.0.type', 'pdf')
            ->where('preview.resources.0.typeLabel', 'PDF')
            ->where('preview.resources.0.categoryLabel', 'فایل‌های تمرین و پروژه')
            ->where('preview.resources.0.isAvailable', true)
            ->where('preview.resources.0.actionLabel', 'مشاهده')
            ->where('preview.resources.0.actionUrl', route('course.resources.index'))
        );
});

test('draft course resources are hidden from course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-2',
        ]);

    CourseResource::factory()->draft()->create([
        'title' => 'پیش‌نویس مخفی',
    ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع قابل مشاهده',
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.resources', 1)
            ->where('preview.resources.0.title', 'منبع قابل مشاهده')
        );
});

test('future published course resource is hidden from course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-3',
        ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع زمان‌بندی‌شده',
        'published_at' => now()->addDay(),
    ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع قابل مشاهده',
        'published_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.resources', 1)
            ->where('preview.resources.0.title', 'منبع قابل مشاهده')
        );
});

test('course home limits published resources preview to three items', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-4',
        ]);

    CourseResource::factory()->count(4)->published()->create();

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('preview.resources', 3));
});

test('user without access is still redirected from course home', function () {
    CourseResource::factory()->published()->create([
        'title' => 'منبع عمومی',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertRedirect(route('profile'));
});

test('course home resource props include persian jalali published at label', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-JALALI',
        ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع با تاریخ شمسی',
        'published_at' => '2026-06-01 14:30:00',
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.resources.0.publishedAtLabel', '۱۱ خرداد ۱۴۰۵')
            ->where('preview.resources.0.publishedAtLabel', fn (string $label): bool => ! str_contains($label, 'June')
                && ! str_contains($label, '2026'))
        );
});

test('active student sees empty resources placeholder when none are published', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-5',
        ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('preview.resources', 0));
});

test('package specific resources are hidden from course home for now', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-SCOPE',
        ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع بسته خاص',
        'access_scope' => CourseResourceAccessScope::PackageSpecific,
        'course_package_id' => $package->id,
    ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع همه هنرجوها',
        'access_scope' => CourseResourceAccessScope::AllStudents,
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.resources', 1)
            ->where('preview.resources.0.title', 'منبع همه هنرجوها')
        );
});

test('resources in inactive categories are hidden from course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-CAT',
        ]);

    $inactiveCategory = CourseResourceCategory::factory()->inactive()->create();

    CourseResource::factory()->published()->create([
        'title' => 'منبع دسته غیرفعال',
        'course_resource_category_id' => $inactiveCategory->id,
    ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع بدون دسته',
        'course_resource_category_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.resources', 1)
            ->where('preview.resources.0.title', 'منبع بدون دسته')
        );
});

test('published resource without file or link is marked unavailable on course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-UNAVAIL',
        ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع بدون فایل',
        'file_path' => null,
        'external_url' => null,
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.resources.0.isAvailable', false)
            ->where('preview.resources.0.actionUrl', null)
        );
});

test('external link resource uses view action label on course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-LINK',
        ]);

    CourseResource::factory()->published()->externalLink()->create([
        'title' => 'لینک رفرنس',
        'external_url' => 'https://example.com/reference',
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.resources.0.type', 'external_link')
            ->where('preview.resources.0.typeLabel', 'لینک بیرونی')
            ->where('preview.resources.0.actionLabel', 'مشاهده')
            ->where('preview.resources.0.actionUrl', route('course.resources.index'))
        );
});

test('scheduled course resource becomes visible after published_at passes', function () {
    config(['app.timezone' => 'Asia/Tehran']);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-SCHEDULE',
        ]);

    Carbon::setTestNow(Carbon::parse('2026-06-01 14:29:00', 'Asia/Tehran'));

    CourseResource::factory()->published()->create([
        'title' => 'منبع دقیقه‌ای',
        'published_at' => Carbon::parse('2026-06-01 14:30:00', 'Asia/Tehran'),
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('preview.resources', 0));

    Carbon::setTestNow(Carbon::parse('2026-06-01 14:30:00', 'Asia/Tehran'));

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.resources', 1)
            ->where('preview.resources.0.title', 'منبع دقیقه‌ای')
        );

    Carbon::setTestNow();
});

test('course home preview exposes resources index url when resources exist', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-URL',
        ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع نمونه',
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.resourcesIndexUrl', route('course.resources.index'))
        );
});

test('active student can visit course resources index', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-INDEX',
        ]);

    $filePath = placeStudentLibraryFile('practice-files', 'week-1.pdf', 'pdf');

    CourseResource::factory()->published()->create([
        'title' => 'فایل تمرین کامل',
        'file_path' => $filePath,
        'course_resource_category_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('animatorsho/course-resources')
            ->where('totalCount', 1)
            ->has('sections', 1)
            ->where('sections.0.title', 'فایل‌های تمرین و پروژه')
            ->where('sections.0.resources.0.title', 'فایل تمرین کامل')
        );
});

test('user without access is redirected from course resources index', function () {
    CourseResource::factory()->published()->create([
        'title' => 'منبع محرمانه',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertRedirect(route('profile'));
});

test('guest cannot access course resources index and is redirected to login', function () {
    $this->get(route('course.resources.index'))
        ->assertRedirect(route('login'));
});

test('course resources index shows all published resources while home preview stays limited', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-ALL',
        ]);

    foreach (range(1, 5) as $index) {
        placeStudentLibraryFile('practice-files', "practice-{$index}.pdf", 'pdf');
    }

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('preview.resources', 3));

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalCount', 5)
            ->has('sections', 1)
            ->has('sections.0.resources', 5)
        );
});

test('draft resources are hidden from course resources index', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-INDEX-DRAFT',
        ]);

    CourseResource::factory()->draft()->create([
        'title' => 'پیش‌نویس مخفی',
    ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع منتشرشده',
    ]);

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalCount', 1)
            ->where('sections.0.resources.0.title', 'منبع منتشرشده')
        );
});

test('future scheduled resources are hidden from course resources index', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-INDEX-FUTURE',
        ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع آینده',
        'published_at' => now()->addDay(),
    ]);

    CourseResource::factory()->published()->create([
        'title' => 'منبع قابل مشاهده',
        'published_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalCount', 1)
            ->where('sections.0.resources.0.title', 'منبع قابل مشاهده')
        );
});

test('resources in inactive categories are hidden from course resources index', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-INDEX-CAT',
        ]);

    $inactiveCategory = CourseResourceCategory::factory()->inactive()->create();

    $hiddenPath = placeStudentLibraryFile('practice-files', 'hidden.pdf', 'pdf');
    placeStudentLibraryFile('practice-files', 'visible.pdf', 'pdf');

    CourseResource::factory()->published()->create([
        'title' => 'منبع دسته غیرفعال',
        'file_path' => $hiddenPath,
        'course_resource_category_id' => $inactiveCategory->id,
    ]);

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalCount', 1)
            ->where('sections.0.resources.0.title', 'visible')
        );
});

test('course resources index sections resources by predefined library categories', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-SECTIONS',
        ]);

    placeStudentLibraryFile('references', 'ref-a.png', 'png');
    $practicePath = placeStudentLibraryFile('practice-files', 'week-1.pdf', 'pdf');

    CourseResource::factory()->published()->create([
        'title' => 'تمرین هفته اول',
        'file_path' => $practicePath,
        'course_resource_category_id' => CourseResourcePredefinedCategories::idFor(
            CourseResourceLibraryCategory::PracticeFiles,
        ),
    ]);

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalCount', 2)
            ->has('sections', 2)
            ->where('sections.0.title', 'رفرنس‌ها')
            ->where('sections.1.title', 'فایل‌های تمرین و پروژه')
        );
});

test('course resources index shows empty state when no published resources exist', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RESOURCES-INDEX-EMPTY',
        ]);

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalCount', 0)
            ->has('sections', 0)
        );
});

test('auto-detected image appears on course resources index without database record', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-AUTO-IMAGE',
        ]);

    placeStudentLibraryFile('references', 'pose-reference.png', 'png');

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalCount', 1)
            ->where('sections.0.title', 'رفرنس‌ها')
            ->where('sections.0.layout', 'masonry')
            ->where('sections.0.resources.0.title', 'pose reference')
            ->where('sections.0.resources.0.isAvailable', true)
        );
});

test('auto-detected practice file appears with download link', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-AUTO-PRACTICE',
        ]);

    $filePath = placeStudentLibraryFile('practice-files', 'week-1-homework.pdf', 'pdf');

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.layout', 'list')
            ->where('sections.0.resources.0.actionLabel', 'دانلود')
            ->where('sections.0.resources.0.actionUrl', $filePath)
        );
});

test('draft database record hides detected library file with same path', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-DRAFT-HIDE',
        ]);

    $filePath = placeStudentLibraryFile('practice-files', 'hidden-draft.pdf', 'pdf');

    CourseResource::factory()->draft()->create([
        'title' => 'پیش‌نویس مخفی',
        'file_path' => $filePath,
    ]);

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalCount', 0)
            ->has('sections', 0)
        );
});

test('database metadata overrides fallback title for matching library file path', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-DB-OVERRIDE',
        ]);

    $filePath = placeStudentLibraryFile('references', 'raw-name.png', 'png');

    CourseResource::factory()->published()->create([
        'title' => 'رفرنس حالت بدن',
        'description' => 'برای تمرین طراحی',
        'file_path' => $filePath,
        'type' => CourseResourceType::Image,
        'course_resource_category_id' => CourseResourcePredefinedCategories::idFor(
            CourseResourceLibraryCategory::References,
        ),
    ]);

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalCount', 1)
            ->where('sections.0.resources.0.title', 'رفرنس حالت بدن')
            ->where('sections.0.resources.0.description', 'برای تمرین طراحی')
            ->where('sections.0.layout', 'masonry')
        );
});

test('course resources index includes descriptions for masonry media items', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-MEDIA-DESC',
        ]);

    $filePath = placeStudentLibraryFile('references', 'character-pose.png', 'png');

    CourseResource::factory()->published()->create([
        'title' => 'رفرنس حالت شخصیت',
        'description' => 'برای تمرین طراحی حالت بدن',
        'file_path' => $filePath,
        'type' => CourseResourceType::Image,
        'course_resource_category_id' => CourseResourcePredefinedCategories::idFor(
            CourseResourceLibraryCategory::References,
        ),
    ]);

    $this->actingAs($user)
        ->get(route('course.resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.layout', 'masonry')
            ->where('sections.0.resources.0.description', 'برای تمرین طراحی حالت بدن')
            ->where('sections.0.resources.0.previewUrl', $filePath)
        );
});
