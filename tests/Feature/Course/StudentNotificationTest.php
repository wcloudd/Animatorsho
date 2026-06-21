<?php

use App\Models\CoursePackage;
use App\Models\SpotPlayerLicense;
use App\Models\StudentNotification;
use App\Models\User;
use App\Services\StudentNotificationService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AnimatorshoCourseSeeder::class);
});

function createNotificationStudent(): array
{
    $user = User::factory()->create(['name' => 'هنرجوی اعلان']);
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-NOTIF-ACCESS',
        ]);

    return [$user, $package];
}

test('course home includes notifications with unread count and items', function () {
    [$user] = createNotificationStudent();

    StudentNotification::create([
        'user_id' => $user->id,
        'type' => StudentNotificationService::TYPE_MEDAL_AWARDED,
        'title' => 'مدال جدید گرفتی',
        'body' => 'مدال نوشتن اولین داستان برای تو ثبت شد.',
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('animatorsho/course-home')
            ->has('notifications')
            ->where('notifications.unreadCount', 1)
            ->has('notifications.items', 1)
            ->where('notifications.items.0.title', 'مدال جدید گرفتی')
            ->where('notifications.items.0.isUnread', true));
});

test('course home shows zero unread when all notifications are read', function () {
    [$user] = createNotificationStudent();

    StudentNotification::create([
        'user_id' => $user->id,
        'type' => StudentNotificationService::TYPE_ADMIN_MESSAGE,
        'title' => 'یک پیام',
        'body' => 'متن پیام.',
        'read_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.unreadCount', 0)
            ->where('notifications.items.0.isUnread', false));
});

test('student can mark one notification as read', function () {
    [$user] = createNotificationStudent();

    $notification = StudentNotification::create([
        'user_id' => $user->id,
        'type' => StudentNotificationService::TYPE_EXERCISE_REVIEWED,
        'title' => 'تمرینت تأیید شد',
    ]);

    $this->actingAs($user)
        ->patch(route('course.notifications.mark-read', $notification))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('student can mark all notifications as read', function () {
    [$user] = createNotificationStudent();

    StudentNotification::create([
        'user_id' => $user->id,
        'type' => StudentNotificationService::TYPE_EXERCISE_REVIEWED,
        'title' => 'اعلان اول',
    ]);

    StudentNotification::create([
        'user_id' => $user->id,
        'type' => StudentNotificationService::TYPE_MEDAL_AWARDED,
        'title' => 'اعلان دوم',
    ]);

    $this->actingAs($user)
        ->post(route('course.notifications.mark-all-read'))
        ->assertRedirect();

    expect(
        StudentNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count()
    )->toBe(0);
});

test('student cannot mark another student notification as read', function () {
    [$user] = createNotificationStudent();
    $other = User::factory()->create();

    $notification = StudentNotification::create([
        'user_id' => $other->id,
        'type' => StudentNotificationService::TYPE_EXERCISE_REVIEWED,
        'title' => 'اعلان دیگری',
    ]);

    $this->actingAs($user)
        ->patch(route('course.notifications.mark-read', $notification))
        ->assertForbidden();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('guest cannot mark notification as read', function () {
    $user = User::factory()->create();

    $notification = StudentNotification::create([
        'user_id' => $user->id,
        'type' => StudentNotificationService::TYPE_EXERCISE_REVIEWED,
        'title' => 'اعلان',
    ]);

    $this->patch(route('course.notifications.mark-read', $notification))
        ->assertRedirect(route('login'));
});

test('guest cannot use mark-all-read route', function () {
    $this->post(route('course.notifications.mark-all-read'))
        ->assertRedirect(route('login'));
});
