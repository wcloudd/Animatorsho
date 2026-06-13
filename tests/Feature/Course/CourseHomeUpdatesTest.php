<?php

use App\Enums\CourseUpdateType;
use App\Enums\CourseUpdateVisualTheme;
use App\Models\CoursePackage;
use App\Models\CourseUpdate;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AnimatorshoCourseSeeder::class);
});

test('active student sees published course updates on course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-UPDATES-1',
        ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'جلسه جدید اضافه شد',
        'summary' => 'فصل دوم به‌روزرسانی شد.',
        'type' => CourseUpdateType::LessonUpdate,
        'visual_theme' => CourseUpdateVisualTheme::Blue,
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.updates', 1)
            ->where('preview.updates.0.title', 'جلسه جدید اضافه شد')
            ->where('preview.updates.0.type', 'lesson_update')
            ->where('preview.updates.0.typeLabel', 'به‌روزرسانی دوره')
            ->where('preview.updates.0.visualTheme', 'blue')
            ->where('preview.updates.0.visualThemeLabel', 'آموزشی / آبی')
            ->where('preview.updates.0.isPinned', false)
        );
});

test('draft course updates are hidden from course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-UPDATES-2',
        ]);

    CourseUpdate::factory()->draft()->create([
        'title' => 'پیش‌نویس مخفی',
    ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت قابل مشاهده',
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.updates', 1)
            ->where('preview.updates.0.title', 'آپدیت قابل مشاهده')
        );
});

test('pinned course update appears before normal update on course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-UPDATES-3',
        ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت جدیدتر',
        'published_at' => now(),
        'is_pinned' => false,
    ]);

    CourseUpdate::factory()->published()->pinned()->create([
        'title' => 'آپدیت سنجاق‌شده',
        'published_at' => now()->subDays(3),
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.updates', 2)
            ->where('preview.updates.0.title', 'آپدیت سنجاق‌شده')
            ->where('preview.updates.0.isPinned', true)
            ->where('preview.updates.1.title', 'آپدیت جدیدتر')
        );
});

test('course home limits published updates preview to three items', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-UPDATES-4',
        ]);

    CourseUpdate::factory()->count(4)->published()->create();

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('preview.updates', 3));
});

test('user without access is still redirected from course home', function () {
    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت عمومی',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertRedirect(route('profile'));
});

test('published update body is included in course home props', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-BODY-1',
        ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت با متن کامل',
        'body' => "خط اول متن\nخط دوم متن",
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.updates.0.body', "خط اول متن\nخط دوم متن")
        );
});

test('draft update body is not exposed on course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-BODY-2',
        ]);

    CourseUpdate::factory()->draft()->create([
        'title' => 'پیش‌نویس مخفی',
        'body' => 'متن مخفی پیش‌نویس',
    ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت منتشرشده',
        'body' => null,
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.updates', 1)
            ->where('preview.updates.0.title', 'آپدیت منتشرشده')
            ->where('preview.updates.0.body', null)
        );
});

test('user without access cannot see published update body on course home', function () {
    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت محرمانه',
        'body' => 'این متن نباید برای کاربر بدون دسترسی دیده شود.',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertRedirect(route('profile'));
});

test('rainbow visual theme is present in course home update props', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-RAINBOW-1',
        ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت بزرگ',
        'visual_theme' => CourseUpdateVisualTheme::Rainbow,
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.updates.0.visualTheme', 'rainbow')
            ->where('preview.updates.0.visualThemeLabel', 'آپدیت بزرگ / رنگین‌کمانی')
        );
});

test('active student sees empty updates placeholder when none are published', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-UPDATES-5',
        ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('preview.updates', 0));
});

test('published update with past published_at appears on course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-PAST-1',
        ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت گذشته',
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.updates', 1)
            ->where('preview.updates.0.title', 'آپدیت گذشته')
        );
});

test('published update with current published_at appears on course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-NOW-1',
        ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت همین الان',
        'published_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.updates', 1)
            ->where('preview.updates.0.title', 'آپدیت همین الان')
        );
});

test('published update with future published_at is hidden from course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-FUTURE-1',
        ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت زمان‌بندی‌شده',
        'published_at' => now()->addDay(),
    ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت قابل مشاهده',
        'published_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.updates', 1)
            ->where('preview.updates.0.title', 'آپدیت قابل مشاهده')
        );
});

test('course home update props include persian jalali published at label', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-JALALI-1',
        ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت با تاریخ شمسی',
        'published_at' => '2026-06-01 14:30:00',
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.updates.0.publishedAtLabel', '۱۱ خرداد ۱۴۰۵')
            ->where('preview.updates.0.publishedAtLabel', fn (string $label): bool => ! str_contains($label, 'June')
                && ! str_contains($label, '2026'))
        );
});

test('pinned ordering applies only among visible published updates', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-PIN-VISIBLE-1',
        ]);

    CourseUpdate::factory()->published()->pinned()->create([
        'title' => 'سنجاق آینده',
        'published_at' => now()->addWeek(),
    ]);

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت عادی',
        'published_at' => now()->subDay(),
        'is_pinned' => false,
    ]);

    CourseUpdate::factory()->published()->pinned()->create([
        'title' => 'سنجاق قابل مشاهده',
        'published_at' => now()->subDays(2),
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.updates', 2)
            ->where('preview.updates.0.title', 'سنجاق قابل مشاهده')
            ->where('preview.updates.1.title', 'آپدیت عادی')
        );
});

test('scheduled course update becomes visible after published_at passes', function () {
    config(['app.timezone' => 'Asia/Tehran']);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-SCHEDULE-1',
        ]);

    Carbon::setTestNow(Carbon::parse('2026-06-01 14:29:00', 'Asia/Tehran'));

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت دقیقه‌ای',
        'published_at' => Carbon::parse('2026-06-01 14:30:00', 'Asia/Tehran'),
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('preview.updates', 0));

    Carbon::setTestNow(Carbon::parse('2026-06-01 14:30:00', 'Asia/Tehran'));

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.updates', 1)
            ->where('preview.updates.0.title', 'آپدیت دقیقه‌ای')
        );

    Carbon::setTestNow();
});
