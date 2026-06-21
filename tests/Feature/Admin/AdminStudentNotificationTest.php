<?php

use App\Enums\ExerciseSubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\StudentNotification;
use App\Models\User;
use App\Services\StudentMedalService;
use App\Services\StudentNotificationService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AnimatorshoCourseSeeder::class);
    Storage::fake('local');
});

test('admin can access student notifications page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.student-notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/student-notifications/index'));
});

test('guest cannot access admin student notifications page', function () {
    $this->get(route('admin.student-notifications.index'))
        ->assertRedirect(route('login'));
});

test('admin can send manual notification to student', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.student-notifications.store'), [
            'user_id' => $student->id,
            'title' => 'یک پیام مهم',
            'body' => 'لطفاً به این موضوع توجه کنید.',
            'action_url' => '/course/exercises',
        ])
        ->assertRedirect(route('admin.student-notifications.index'));

    $notification = StudentNotification::query()
        ->where('user_id', $student->id)
        ->where('type', StudentNotificationService::TYPE_ADMIN_MESSAGE)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('یک پیام مهم')
        ->and($notification->body)->toBe('لطفاً به این موضوع توجه کنید.')
        ->and($notification->action_url)->toBe('/course/exercises');
});

test('invalid manual notification input is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.student-notifications.store'), [
            'user_id' => 99999,
            'title' => '',
            'body' => '',
        ])
        ->assertSessionHasErrors(['user_id', 'title', 'body']);

    expect(StudentNotification::query()->count())->toBe(0);
});

test('medal award creates student notification', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.student-medals.store'), [
            'user_id' => $student->id,
            'medal_key' => 'first_story_written',
            'note' => null,
        ])
        ->assertRedirect(route('admin.student-medals.index'));

    $notification = StudentNotification::query()
        ->where('user_id', $student->id)
        ->where('type', StudentNotificationService::TYPE_MEDAL_AWARDED)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('مدال جدید گرفتی')
        ->and($notification->body)->toContain(StudentMedalService::MEDALS['first_story_written']);
});

test('exercise approved creates notification', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین اول',
        'status' => ExerciseSubmissionStatus::Submitted,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.exercise-submissions.update', $submission), [
            'status' => 'approved',
            'admin_feedback' => null,
            'xp_award' => 0,
        ])
        ->assertRedirect();

    $notification = StudentNotification::query()
        ->where('user_id', $student->id)
        ->where('type', StudentNotificationService::TYPE_EXERCISE_REVIEWED)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('تمرینت تأیید شد');
});

test('needs revision creates notification', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'status' => ExerciseSubmissionStatus::Submitted,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.exercise-submissions.update', $submission), [
            'status' => 'needs_revision',
            'admin_feedback' => null,
            'xp_award' => 0,
        ])
        ->assertRedirect();

    $notification = StudentNotification::query()
        ->where('user_id', $student->id)
        ->where('type', StudentNotificationService::TYPE_EXERCISE_REVIEWED)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('تمرینت نیاز به اصلاح دارد');
});

test('teacher feedback creates notification', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'status' => ExerciseSubmissionStatus::Reviewing,
        'admin_feedback' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.exercise-submissions.update', $submission), [
            'status' => 'reviewing',
            'admin_feedback' => 'کار خوبی بود.',
            'xp_award' => 0,
        ])
        ->assertRedirect();

    $notification = StudentNotification::query()
        ->where('user_id', $student->id)
        ->where('type', StudentNotificationService::TYPE_TEACHER_FEEDBACK_ADDED)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('بازخورد استاد ثبت شد');
});

test('teacher feedback attachment creates notification', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'status' => ExerciseSubmissionStatus::Reviewing,
    ]);

    $file = UploadedFile::fake()->create('feedback.pdf', 100, 'application/pdf');

    $this->actingAs($admin)
        ->post(route('admin.exercise-submissions.feedback-attachments.store', $submission), [
            'feedback_files' => [$file],
        ])
        ->assertRedirect();

    $notification = StudentNotification::query()
        ->where('user_id', $student->id)
        ->where('type', StudentNotificationService::TYPE_TEACHER_FEEDBACK_ATTACHMENT_ADDED)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('فایل استاد برای تمرینت اضافه شد');
});

test('saving same status twice does not create duplicate exercise reviewed notification', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'status' => ExerciseSubmissionStatus::Submitted,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.exercise-submissions.update', $submission), [
            'status' => 'approved',
            'admin_feedback' => null,
            'xp_award' => 0,
        ]);

    $this->actingAs($admin)
        ->patch(route('admin.exercise-submissions.update', $submission), [
            'status' => 'approved',
            'admin_feedback' => null,
            'xp_award' => 0,
        ]);

    expect(
        StudentNotification::query()
            ->where('user_id', $student->id)
            ->where('type', StudentNotificationService::TYPE_EXERCISE_REVIEWED)
            ->count()
    )->toBe(1);
});
