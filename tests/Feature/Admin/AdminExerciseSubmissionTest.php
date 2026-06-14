<?php

use App\Enums\ExerciseSubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AnimatorshoCourseSeeder::class);
    Storage::fake('local');
});

test('guest cannot access admin exercise submissions', function () {
    $this->get(route('admin.exercise-submissions.index'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access admin exercise submissions', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.exercise-submissions.index'))
        ->assertForbidden();
});

test('admin can list exercise submissions', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create(['name' => 'هنرجوی تست', 'mobile' => '09121234567']);

    ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین اول',
        'submission_url' => 'https://example.com/1',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.exercise-submissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/exercise-submissions/index')
            ->has('submissions.data', 1)
            ->where('submissions.data.0.title', 'تمرین اول')
            ->where('submissions.data.0.studentName', 'هنرجوی تست')
            ->where('submissions.data.0.studentMobile', '09121234567')
            ->where('submissions.data.0.status', 'ارسال‌شده'));
});

test('admin index includes jalali submitted date label', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین تاریخ',
        'submission_url' => 'https://example.com/date',
    ]);

    $submission->forceFill([
        'created_at' => '2026-06-01 14:30:00',
        'updated_at' => '2026-06-01 14:30:00',
    ])->save();

    $this->actingAs($admin)
        ->get(route('admin.exercise-submissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('submissions.data.0.submittedAtLabel', '۱۱ خرداد ۱۴۰۵، ۱۴:۳۰')
            ->where('submissions.data.0.submittedAtLabel', fn (string $label): bool => ! str_contains($label, 'June')
                && ! str_contains($label, '2026')));
});

test('admin can view exercise submission detail', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create(['name' => 'هنرجو']);

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین جزئیات',
        'description' => 'توضیح تمرین',
        'submission_url' => 'https://example.com/detail',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.exercise-submissions.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/exercise-submissions/show')
            ->where('submission.id', $submission->id)
            ->where('submission.title', 'تمرین جزئیات')
            ->where('submission.studentName', 'هنرجو')
            ->where('submission.submissionLink', 'https://example.com/detail'));
});

test('admin can review and update status and feedback', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین بررسی',
        'submission_url' => 'https://example.com/review',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.exercise-submissions.update', $submission), [
            'status' => ExerciseSubmissionStatus::Approved->value,
            'admin_feedback' => 'عالی بود!',
        ])
        ->assertRedirect();

    $fresh = $submission->fresh();

    expect($fresh->status)->toBe(ExerciseSubmissionStatus::Approved)
        ->and($fresh->admin_feedback)->toBe('عالی بود!')
        ->and($fresh->reviewed_by)->toBe($admin->id)
        ->and($fresh->reviewed_at)->not->toBeNull();
});

test('non-admin cannot update exercise submissions', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $submission = ExerciseSubmission::factory()->create();

    $this->actingAs($user)
        ->patch(route('admin.exercise-submissions.update', $submission), [
            'status' => ExerciseSubmissionStatus::Approved->value,
            'admin_feedback' => 'نباید ثبت شود',
        ])
        ->assertForbidden();
});

test('admin navigation manifest includes exercise submissions link', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(function (Assert $page): void {
            $groups = $page->toArray()['props']['adminNavGroups'] ?? [];
            $items = collect($groups)->flatMap(fn (array $group): array => $group['items'] ?? []);

            expect($items->firstWhere('route', 'admin.exercise-submissions.index'))->not->toBeNull();
        });
});

test('admin can download exercise attachment', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین فایل',
        'submission_url' => null,
        'attachment_disk' => 'local',
        'attachment_path' => 'exercise-submissions/1/sample.pdf',
        'attachment_original_name' => 'sample.pdf',
        'attachment_mime_type' => 'application/pdf',
        'attachment_size_bytes' => 1024,
    ]);

    Storage::disk('local')->put($submission->attachment_path, 'pdf-content');

    $this->actingAs($admin)
        ->get(route('admin.exercise-submissions.attachment', $submission))
        ->assertOk();
});

test('admin can delete exercise attachment without deleting submission', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین حذف فایل',
        'submission_url' => 'https://example.com/keep',
        'attachment_disk' => 'local',
        'attachment_path' => 'exercise-submissions/2/remove.png',
        'attachment_original_name' => 'remove.png',
        'attachment_mime_type' => 'image/png',
        'attachment_size_bytes' => 2048,
    ]);

    Storage::disk('local')->put($submission->attachment_path, 'png-content');

    $this->actingAs($admin)
        ->delete(route('admin.exercise-submissions.attachment.destroy', $submission))
        ->assertRedirect();

    $fresh = $submission->fresh();

    expect($fresh)->not->toBeNull()
        ->and($fresh->title)->toBe('تمرین حذف فایل')
        ->and($fresh->hasActiveAttachment())->toBeFalse()
        ->and($fresh->attachment_deleted_at)->not->toBeNull()
        ->and($fresh->attachment_deleted_by)->toBe($admin->id);

    Storage::disk('local')->assertMissing('exercise-submissions/2/remove.png');
});

test('admin show includes attachment metadata', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'متادیتا',
        'attachment_disk' => 'local',
        'attachment_path' => 'exercise-submissions/3/meta.zip',
        'attachment_original_name' => 'project.zip',
        'attachment_mime_type' => 'application/zip',
        'attachment_size_bytes' => 4096,
    ]);

    Storage::disk('local')->put($submission->attachment_path, 'zip-content');

    $this->actingAs($admin)
        ->get(route('admin.exercise-submissions.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('submission.attachment.originalName', 'project.zip')
            ->where('submission.attachment.sizeBytes', 4096)
            ->where('submission.attachment.extension', 'zip')
            ->where('submission.attachment.isDeleted', false));
});

test('admin attachment overview shows total count and size', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create(['name' => 'هنرجو']);

    ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'فایل اول',
        'attachment_disk' => 'local',
        'attachment_path' => 'exercise-submissions/4/a.png',
        'attachment_original_name' => 'a.png',
        'attachment_size_bytes' => 1000,
    ]);

    ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'فایل دوم',
        'attachment_disk' => 'local',
        'attachment_path' => 'exercise-submissions/4/b.png',
        'attachment_original_name' => 'b.png',
        'attachment_size_bytes' => 2000,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.exercise-attachments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/exercise-attachments/index')
            ->where('summary.totalCount', 2)
            ->where('summary.totalSizeBytes', 3000)
            ->has('attachments.data', 2));
});

test('non-admin cannot access admin attachment routes', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $submission = ExerciseSubmission::factory()->create([
        'attachment_disk' => 'local',
        'attachment_path' => 'exercise-submissions/9/x.pdf',
        'attachment_original_name' => 'x.pdf',
    ]);

    $this->actingAs($user)->get(route('admin.exercise-submissions.attachment', $submission))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.exercise-submissions.attachment.destroy', $submission))->assertForbidden();
    $this->actingAs($user)->get(route('admin.exercise-attachments.index'))->assertForbidden();
});
