<?php

use App\Enums\CourseUpdateStatus;
use App\Enums\CourseUpdateType;
use App\Enums\CourseUpdateVisualTheme;
use App\Models\CourseUpdate;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('guest cannot access admin course updates', function () {
    $this->get(route('admin.course-updates.index'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access admin course updates', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.course-updates.index'))
        ->assertForbidden();
});

test('admin can view course updates index', function () {
    $admin = User::factory()->admin()->create();

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت تستی',
        'visual_theme' => CourseUpdateVisualTheme::Gold,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.course-updates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/course-updates/index')
            ->has('updates.data', 1)
            ->where('updates.data.0.title', 'آپدیت تستی')
            ->where('updates.data.0.visualTheme', 'gold')
            ->where('updates.data.0.visualThemeLabel', 'طلایی')
            ->where('updates.data.0.statusLabel', 'منتشرشده')
        );
});

test('admin can create a draft course update', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.course-updates.store'), [
            'title' => 'پیش‌نویس جدید',
            'summary' => 'خلاصه کوتاه',
            'body' => null,
            'type' => CourseUpdateType::Announcement->value,
            'visual_theme' => CourseUpdateVisualTheme::Purple->value,
            'status' => CourseUpdateStatus::Draft->value,
            'is_pinned' => false,
            'display_order' => 2,
            'published_at' => null,
        ])
        ->assertRedirect();

    $update = CourseUpdate::query()->first();

    expect($update)->not->toBeNull()
        ->and($update->title)->toBe('پیش‌نویس جدید')
        ->and($update->status)->toBe(CourseUpdateStatus::Draft)
        ->and($update->visual_theme)->toBe(CourseUpdateVisualTheme::Purple)
        ->and($update->published_at)->toBeNull();
});

test('admin can create a published course update', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.course-updates.store'), [
            'title' => 'آپدیت منتشرشده',
            'summary' => 'خلاصه',
            'body' => 'متن کامل',
            'type' => CourseUpdateType::Important->value,
            'visual_theme' => CourseUpdateVisualTheme::Rainbow->value,
            'status' => CourseUpdateStatus::Published->value,
            'is_pinned' => true,
            'display_order' => 0,
            'published_at' => null,
        ])
        ->assertRedirect();

    $update = CourseUpdate::query()->first();

    expect($update)->not->toBeNull()
        ->and($update->status)->toBe(CourseUpdateStatus::Published)
        ->and($update->published_at)->not->toBeNull()
        ->and($update->is_pinned)->toBeTrue()
        ->and($update->visual_theme)->toBe(CourseUpdateVisualTheme::Rainbow);
});

test('admin can edit course update visual theme', function () {
    $admin = User::factory()->admin()->create();

    $update = CourseUpdate::factory()->published()->create([
        'visual_theme' => CourseUpdateVisualTheme::Default,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.course-updates.update', $update), [
            'title' => $update->title,
            'summary' => $update->summary,
            'body' => $update->body,
            'type' => $update->type->value,
            'visual_theme' => CourseUpdateVisualTheme::Yellow->value,
            'status' => CourseUpdateStatus::Published->value,
            'is_pinned' => false,
            'display_order' => 0,
            'published_at' => $update->published_at?->toIso8601String(),
        ])
        ->assertRedirect(route('admin.course-updates.index'));

    expect($update->fresh()->visual_theme)->toBe(CourseUpdateVisualTheme::Yellow);
});

test('admin create form exposes lesson update label as course update in persian', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.course-updates.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/course-updates/create')
            ->has('formOptions.typeOptions')
            ->where('formOptions.typeOptions', fn ($options): bool => collect($options)->contains(
                fn (array $option): bool => $option['value'] === CourseUpdateType::LessonUpdate->value
                    && $option['label'] === 'به‌روزرسانی دوره',
            ))
        );
});

test('admin can store published_at with gregorian date and time', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.course-updates.store'), [
            'title' => 'آپدیت با تاریخ مشخص',
            'summary' => null,
            'body' => null,
            'type' => CourseUpdateType::Announcement->value,
            'visual_theme' => CourseUpdateVisualTheme::Default->value,
            'status' => CourseUpdateStatus::Published->value,
            'is_pinned' => false,
            'display_order' => 0,
            'published_at' => '2026-06-01T14:30',
        ])
        ->assertRedirect();

    $update = CourseUpdate::query()->first();

    expect($update)->not->toBeNull()
        ->and($update->published_at?->format('Y-m-d H:i'))->toBe('2026-06-01 14:30');
});

test('admin course update validation rejects invalid theme status and type', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.course-updates.store'), [
            'title' => '',
            'summary' => null,
            'body' => null,
            'type' => 'invalid-type',
            'visual_theme' => 'neon',
            'status' => 'hidden',
            'is_pinned' => false,
            'display_order' => 0,
            'published_at' => null,
        ])
        ->assertSessionHasErrors(['title', 'type', 'visual_theme', 'status']);
});

test('admin can save published update with future published_at without overwriting to now', function () {
    $admin = User::factory()->admin()->create();
    $futurePublishedAt = now()->addDays(3)->startOfMinute();

    $this->actingAs($admin)
        ->post(route('admin.course-updates.store'), [
            'title' => 'آپدیت زمان‌بندی‌شده',
            'summary' => null,
            'body' => null,
            'type' => CourseUpdateType::Announcement->value,
            'visual_theme' => CourseUpdateVisualTheme::Default->value,
            'status' => CourseUpdateStatus::Published->value,
            'is_pinned' => false,
            'display_order' => 0,
            'published_at' => $futurePublishedAt->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect();

    $update = CourseUpdate::query()->first();

    expect($update)->not->toBeNull()
        ->and($update->status)->toBe(CourseUpdateStatus::Published)
        ->and($update->published_at?->format('Y-m-d H:i'))->toBe($futurePublishedAt->format('Y-m-d H:i'));
});

test('admin index shows scheduled badge for future published updates', function () {
    $admin = User::factory()->admin()->create();

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت آینده',
        'published_at' => now()->addDay(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.course-updates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('updates.data.0.statusLabel', 'زمان‌بندی‌شده')
            ->where('updates.data.0.isScheduled', true)
            ->where('updates.data.0.statusTone', 'warning')
        );
});

test('admin index exposes jalali published at label with time', function () {
    $admin = User::factory()->admin()->create();

    CourseUpdate::factory()->published()->create([
        'title' => 'آپدیت با تاریخ شمسی',
        'published_at' => '2026-06-01 14:30:00',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.course-updates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('updates.data.0.publishedAtLabel', '۱۱ خرداد ۱۴۰۵، ۱۴:۳۰')
            ->where('updates.data.0.publishedAtLabel', fn (string $label): bool => ! str_contains($label, 'June')
                && ! str_contains($label, '2026'))
        );
});

test('admin form datetime is parsed in application timezone', function () {
    config(['app.timezone' => 'Asia/Tehran']);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.course-updates.store'), [
            'title' => 'آپدیت با زمان محلی',
            'summary' => null,
            'body' => null,
            'type' => CourseUpdateType::Announcement->value,
            'visual_theme' => CourseUpdateVisualTheme::Default->value,
            'status' => CourseUpdateStatus::Published->value,
            'is_pinned' => false,
            'display_order' => 0,
            'published_at' => '2026-06-01T14:30',
        ])
        ->assertRedirect();

    $update = CourseUpdate::query()->first();

    expect($update)->not->toBeNull()
        ->and($update->published_at?->timezone('Asia/Tehran')->format('Y-m-d H:i'))->toBe('2026-06-01 14:30');
});
