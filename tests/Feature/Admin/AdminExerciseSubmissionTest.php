<?php

use App\Enums\ExerciseSubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\ExerciseSubmissionAttachment;
use App\Models\ExerciseSubmissionFeedbackAttachment;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Illuminate\Http\UploadedFile;
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
            ->where('submissions.data.0.status', 'ارسال‌شده')
            ->where('submissions.data.0.attachmentCount', 0)
            ->where('submissions.data.0.reviewedAtLabel', '—'));
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

test('admin submission show lists all attachments', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'چند فایلی',
    ]);

    $first = ExerciseSubmissionAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'a.png',
        'path' => 'exercise-submissions/'.$student->id.'/'.$submission->id.'/a.png',
    ]);
    Storage::disk('local')->put($first->path, 'png-a');

    $second = ExerciseSubmissionAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'b.pdf',
        'path' => 'exercise-submissions/'.$student->id.'/'.$submission->id.'/b.pdf',
    ]);
    Storage::disk('local')->put($second->path, 'pdf-b');

    $this->actingAs($admin)
        ->get(route('admin.exercise-submissions.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('submission.attachments', 2)
            ->where('submission.attachments.0.originalName', 'a.png')
            ->where('submission.attachments.1.originalName', 'b.pdf'));
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
    ]);

    $attachment = ExerciseSubmissionAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'sample.pdf',
        'path' => 'exercise-submissions/'.$student->id.'/'.$submission->id.'/sample.pdf',
    ]);

    Storage::disk('local')->put($attachment->path, 'pdf-content');

    $this->actingAs($admin)
        ->get(route('admin.exercise-submissions.attachments.download', [$submission, $attachment]))
        ->assertOk();
});

test('admin can delete one attachment without deleting submission', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین حذف فایل',
        'submission_url' => 'https://example.com/keep',
    ]);

    $first = ExerciseSubmissionAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'keep.png',
        'path' => 'exercise-submissions/'.$student->id.'/'.$submission->id.'/keep.png',
    ]);
    Storage::disk('local')->put($first->path, 'png-keep');

    $second = ExerciseSubmissionAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'remove.png',
        'path' => 'exercise-submissions/'.$student->id.'/'.$submission->id.'/remove.png',
    ]);
    Storage::disk('local')->put($second->path, 'png-remove');

    $this->actingAs($admin)
        ->delete(route('admin.exercise-submissions.attachments.destroy', [$submission, $second]))
        ->assertRedirect();

    $fresh = $submission->fresh();

    expect($fresh)->not->toBeNull()
        ->and($fresh->title)->toBe('تمرین حذف فایل')
        ->and($fresh->attachments()->whereNull('deleted_at')->count())->toBe(1)
        ->and($second->fresh()->deleted_at)->not->toBeNull()
        ->and($second->fresh()->deleted_by)->toBe($admin->id);

    Storage::disk('local')->assertMissing($second->path);
    Storage::disk('local')->assertExists($first->path);
});

test('admin show includes attachment metadata', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'متادیتا',
    ]);

    $attachment = ExerciseSubmissionAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'project.zip',
        'mime_type' => 'application/zip',
        'size_bytes' => 4096,
        'path' => 'exercise-submissions/'.$student->id.'/'.$submission->id.'/project.zip',
    ]);

    Storage::disk('local')->put($attachment->path, 'zip-content');

    $this->actingAs($admin)
        ->get(route('admin.exercise-submissions.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('submission.attachments.0.originalName', 'project.zip')
            ->where('submission.attachments.0.sizeBytes', 4096)
            ->where('submission.attachments.0.extension', 'zip')
            ->where('submission.attachments.0.isDeleted', false));
});

test('admin attachment overview counts non-deleted attachments and total size', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create(['name' => 'هنرجو']);

    $firstSubmission = ExerciseSubmission::factory()->forUser($student)->create(['title' => 'فایل اول']);
    ExerciseSubmissionAttachment::factory()->forSubmission($firstSubmission)->create([
        'original_name' => 'a.png',
        'size_bytes' => 1000,
        'path' => 'exercise-submissions/'.$student->id.'/'.$firstSubmission->id.'/a.png',
    ]);

    $secondSubmission = ExerciseSubmission::factory()->forUser($student)->create(['title' => 'فایل دوم']);
    ExerciseSubmissionAttachment::factory()->forSubmission($secondSubmission)->create([
        'original_name' => 'b.png',
        'size_bytes' => 2000,
        'path' => 'exercise-submissions/'.$student->id.'/'.$secondSubmission->id.'/b.png',
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
    $submission = ExerciseSubmission::factory()->create(['title' => 'تست']);
    $attachment = ExerciseSubmissionAttachment::factory()->forSubmission($submission)->create([
        'path' => 'exercise-submissions/9/1/x.pdf',
    ]);

    $this->actingAs($user)->get(route('admin.exercise-submissions.attachments.download', [$submission, $attachment]))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.exercise-submissions.attachments.destroy', [$submission, $attachment]))->assertForbidden();
    $this->actingAs($user)->get(route('admin.exercise-attachments.index'))->assertForbidden();
});

test('admin list shows attachment count for submission with files', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create(['name' => 'هنرجو']);

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین چند فایلی',
    ]);

    ExerciseSubmissionAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'a.png',
        'path' => 'exercise-submissions/'.$student->id.'/'.$submission->id.'/a.png',
    ]);
    ExerciseSubmissionAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'b.pdf',
        'path' => 'exercise-submissions/'.$student->id.'/'.$submission->id.'/b.pdf',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.exercise-submissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('submissions.data.0.attachmentCount', 2));
});

test('admin can upload feedback attachment while reviewing', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین با فایل استاد',
    ]);

    $file = UploadedFile::fake()->create('feedback.pdf', 100, 'application/pdf');

    $this->actingAs($admin)
        ->post(route('admin.exercise-submissions.feedback-attachments.store', $submission), [
            'feedback_files' => [$file],
        ])
        ->assertRedirect();

    expect(
        ExerciseSubmissionFeedbackAttachment::query()
            ->where('exercise_submission_id', $submission->id)
            ->whereNull('deleted_at')
            ->count()
    )->toBe(1);
});

test('admin can review without uploading feedback files', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'بررسی بدون فایل',
        'submission_url' => 'https://example.com/review',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.exercise-submissions.update', $submission), [
            'status' => ExerciseSubmissionStatus::Approved->value,
            'admin_feedback' => 'عالی بود',
        ])
        ->assertRedirect();

    expect($submission->fresh()->status)->toBe(ExerciseSubmissionStatus::Approved)
        ->and(ExerciseSubmissionFeedbackAttachment::query()->where('exercise_submission_id', $submission->id)->count())->toBe(0);
});

test('admin can download feedback attachment', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین دانلود استاد',
    ]);

    $attachment = ExerciseSubmissionFeedbackAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'teacher-notes.pdf',
        'path' => 'exercise-submission-feedback/'.$submission->id.'/teacher-notes.pdf',
    ]);

    Storage::disk('local')->put($attachment->path, 'pdf-content');

    $this->actingAs($admin)
        ->get(route('admin.exercise-submissions.feedback-attachments.download', [$submission, $attachment]))
        ->assertOk();
});

test('admin can delete feedback attachment', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین حذف فایل استاد',
    ]);

    $attachment = ExerciseSubmissionFeedbackAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'to-delete.pdf',
        'path' => 'exercise-submission-feedback/'.$submission->id.'/to-delete.pdf',
    ]);

    Storage::disk('local')->put($attachment->path, 'pdf-content');

    $this->actingAs($admin)
        ->delete(route('admin.exercise-submissions.feedback-attachments.destroy', [$submission, $attachment]))
        ->assertRedirect();

    $fresh = $attachment->fresh();
    expect($fresh->deleted_at)->not->toBeNull()
        ->and($fresh->deleted_by)->toBe($admin->id);

    Storage::disk('local')->assertMissing($attachment->path);
});

test('admin show includes feedback attachments', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین با فایل استاد',
    ]);

    ExerciseSubmissionFeedbackAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'notes.pdf',
        'path' => 'exercise-submission-feedback/'.$submission->id.'/notes.pdf',
    ]);

    Storage::disk('local')->put(
        'exercise-submission-feedback/'.$submission->id.'/notes.pdf',
        'content',
    );

    $this->actingAs($admin)
        ->get(route('admin.exercise-submissions.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('submission.feedbackAttachments', 1)
            ->where('submission.feedbackAttachments.0.originalName', 'notes.pdf')
            ->where('submission.feedbackAttachments.0.isDeleted', false));
});

test('non-admin cannot manage feedback attachments', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $submission = ExerciseSubmission::factory()->create(['title' => 'تست']);
    $attachment = ExerciseSubmissionFeedbackAttachment::factory()->forSubmission($submission)->create([
        'path' => 'exercise-submission-feedback/'.$submission->id.'/x.pdf',
    ]);

    $this->actingAs($user)
        ->post(route('admin.exercise-submissions.feedback-attachments.store', $submission), [
            'feedback_files' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')],
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.exercise-submissions.feedback-attachments.download', [$submission, $attachment]))
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('admin.exercise-submissions.feedback-attachments.destroy', [$submission, $attachment]))
        ->assertForbidden();
});

test('validation rejects more than three feedback files', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین تست حداکثر فایل',
    ]);

    $files = array_fill(0, 4, UploadedFile::fake()->create('f.pdf', 10, 'application/pdf'));

    $this->actingAs($admin)
        ->post(route('admin.exercise-submissions.feedback-attachments.store', $submission), [
            'feedback_files' => $files,
        ])
        ->assertSessionHasErrors('feedback_files');
});

test('validation rejects feedback file over 5MB', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین فایل بزرگ',
    ]);

    $bigFile = UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf');

    $this->actingAs($admin)
        ->post(route('admin.exercise-submissions.feedback-attachments.store', $submission), [
            'feedback_files' => [$bigFile],
        ])
        ->assertSessionHasErrors('feedback_files.0');
});

test('admin can still download legacy single-column attachment', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($student)->create([
        'title' => 'تمرین قدیمی',
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
