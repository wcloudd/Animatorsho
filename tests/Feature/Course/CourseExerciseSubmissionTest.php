<?php

use App\Enums\ExerciseSubmissionStatus;
use App\Models\CoursePackage;
use App\Models\ExerciseSubmission;
use App\Models\ExerciseSubmissionAttachment;
use App\Models\ExerciseSubmissionFeedbackAttachment;
use App\Models\SpotPlayerLicense;
use App\Models\StudentXpEvent;
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

function createActiveStudent(): array
{
    $user = User::factory()->create(['name' => 'هنرجوی فعال']);
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-EXERCISE-ACCESS',
        ]);

    return [$user, $package];
}

test('guest cannot access course exercises index', function () {
    $this->get(route('course.exercises.index'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('url.intended', route('course.exercises.index'));
});

test('user without active access is redirected from course exercises index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('course.exercises.index'))
        ->assertRedirect(route('profile'))
        ->assertSessionHas(
            'status',
            'دسترسی فعالی برای دوره ندارید. وضعیت ثبت‌نام و لایسنس را در پروفایل بررسی کنید.',
        );
});

test('active student can open course exercises index', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->get(route('course.exercises.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('animatorsho/course-exercises')
            ->has('submissions', 0)
            ->where('createUrl', route('course.exercises.create')));
});

test('create form does not render submission url field', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->get(route('course.exercises.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('animatorsho/course-exercises-create')
            ->where('maxAttachments', 5));
});

test('exercise submission requires at least one uploaded file', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->from(route('course.exercises.create'))
        ->post(route('course.exercises.store'), [
            'title' => 'تمرین بدون فایل',
            'description' => 'توضیح',
        ])
        ->assertRedirect(route('course.exercises.create'))
        ->assertSessionHasErrors('attachments');

    expect(ExerciseSubmission::query()->count())->toBe(0);
});

test('student can upload one exercise file', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->post(route('course.exercises.store'), [
            'title' => 'تمرین فایل',
            'attachments' => [
                UploadedFile::fake()->image('frame.png'),
            ],
        ])
        ->assertRedirect(route('course.exercises.index'));

    $submission = ExerciseSubmission::query()->with('attachments')->first();

    expect($submission)->not->toBeNull()
        ->and($submission->attachments)->toHaveCount(1)
        ->and($submission->attachments->first()->original_name)->toBe('frame.png')
        ->and($submission->hasActiveAttachment())->toBeTrue();

    Storage::disk('local')->assertExists((string) $submission->attachments->first()->path);
});

test('student can upload up to three exercise files', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->post(route('course.exercises.store'), [
            'title' => 'تمرین سه فایلی',
            'attachments' => [
                UploadedFile::fake()->image('a.png'),
                UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('c.txt', 10, 'text/plain'),
            ],
        ])
        ->assertRedirect(route('course.exercises.index'));

    expect(ExerciseSubmission::query()->first()?->attachments)->toHaveCount(3);
});

test('student can upload up to five exercise files', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->post(route('course.exercises.store'), [
            'title' => 'تمرین پنج فایلی',
            'attachments' => [
                UploadedFile::fake()->image('a.png'),
                UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('c.txt', 10, 'text/plain'),
                UploadedFile::fake()->image('d.jpg'),
                UploadedFile::fake()->create('e.zip', 200, 'application/zip'),
            ],
        ])
        ->assertRedirect(route('course.exercises.index'));

    expect(ExerciseSubmission::query()->first()?->attachments)->toHaveCount(5);
});

test('six exercise files are rejected', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->from(route('course.exercises.create'))
        ->post(route('course.exercises.store'), [
            'title' => 'تمرین شش فایلی',
            'attachments' => [
                UploadedFile::fake()->image('1.png'),
                UploadedFile::fake()->image('2.png'),
                UploadedFile::fake()->image('3.png'),
                UploadedFile::fake()->image('4.png'),
                UploadedFile::fake()->image('5.png'),
                UploadedFile::fake()->image('6.png'),
            ],
        ])
        ->assertRedirect(route('course.exercises.create'))
        ->assertSessionHasErrors('attachments');

    expect(ExerciseSubmission::query()->count())->toBe(0);
});

test('exercise file larger than 5mb is rejected', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->from(route('course.exercises.create'))
        ->post(route('course.exercises.store'), [
            'title' => 'فایل بزرگ',
            'attachments' => [
                UploadedFile::fake()->create('large.zip', 5121, 'application/zip'),
            ],
        ])
        ->assertRedirect(route('course.exercises.create'))
        ->assertSessionHasErrors('attachments.0');

    expect(ExerciseSubmission::query()->count())->toBe(0);
});

test('invalid exercise file type is rejected', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->from(route('course.exercises.create'))
        ->post(route('course.exercises.store'), [
            'title' => 'فایل نامعتبر',
            'attachments' => [
                UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
            ],
        ])
        ->assertRedirect(route('course.exercises.create'))
        ->assertSessionHasErrors('attachments.0');

    expect(ExerciseSubmission::query()->count())->toBe(0);
});

test('student sees only own submissions on exercises index', function () {
    [$owner] = createActiveStudent();
    [$otherStudent] = createActiveStudent();

    $ownerSubmission = ExerciseSubmission::factory()->forUser($owner)->create([
        'title' => 'تمرین من',
        'submission_url' => 'https://example.com/owner',
    ]);

    ExerciseSubmissionAttachment::factory()->forSubmission($ownerSubmission)->create([
        'original_name' => 'mine.png',
        'path' => 'exercise-submissions/'.$owner->id.'/'.$ownerSubmission->id.'/mine.png',
    ]);

    ExerciseSubmission::factory()->forUser($otherStudent)->create([
        'title' => 'تمرین دیگران',
        'submission_url' => 'https://example.com/other',
    ]);

    $this->actingAs($owner)
        ->get(route('course.exercises.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('submissions', 1)
            ->where('submissions.0.title', 'تمرین من')
            ->has('submissions.0.attachments', 1));
});

test('course home preview shows latest submission status and cta', function () {
    [$user] = createActiveStudent();

    ExerciseSubmission::factory()->forUser($user)->create([
        'title' => 'تمرین آخر',
        'submission_url' => 'https://example.com/latest',
        'status' => ExerciseSubmissionStatus::Reviewing,
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.exercisesSummary.total', 1)
            ->where('preview.exercisesSummary.pending', 1)
            ->where('preview.exercisesSummary.latest.title', 'تمرین آخر')
            ->where('preview.exercisesSummary.latest.statusLabel', 'در حال بررسی')
            ->where('preview.exercisesSummary.exercisesIndexUrl', route('course.exercises.index'))
            ->where('preview.exercisesSummary.createUrl', route('course.exercises.create')));
});

test('course home exercises preview shows submit cta when no submissions', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.exercisesSummary.total', 0)
            ->where('preview.exercisesSummary.pending', 0)
            ->where('preview.exercisesSummary.latest', null)
            ->where('preview.exercisesSummary.createUrl', route('course.exercises.create')));
});

test('exercise submission create is rate limited', function () {
    [$user] = createActiveStudent();
    config(['security.rate_limits.exercise-submission-create.max_attempts' => 2]);

    foreach (range(1, 2) as $attempt) {
        $this->actingAs($user)->post(route('course.exercises.store'), [
            'title' => "تمرین {$attempt}",
            'attachments' => [
                UploadedFile::fake()->image("file-{$attempt}.png"),
            ],
        ])->assertRedirect();
    }

    $this->actingAs($user)->post(route('course.exercises.store'), [
        'title' => 'تمرین سوم',
        'attachments' => [
            UploadedFile::fake()->image('file-3.png'),
        ],
    ])->assertStatus(429);
});

test('student can download own exercise attachment', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)->post(route('course.exercises.store'), [
        'title' => 'دانلود من',
        'attachments' => [
            UploadedFile::fake()->image('mine.jpg'),
        ],
    ]);

    $submission = ExerciseSubmission::query()->with('attachments')->firstOrFail();
    $attachment = $submission->attachments->firstOrFail();

    $this->actingAs($user)
        ->get(route('course.exercises.attachments.download', [$submission, $attachment]))
        ->assertOk();
});

test('student cannot download another users exercise attachment', function () {
    [$owner] = createActiveStudent();
    [$otherStudent] = createActiveStudent();

    $this->actingAs($owner)->post(route('course.exercises.store'), [
        'title' => 'فایل خصوصی',
        'attachments' => [
            UploadedFile::fake()->image('private.png'),
        ],
    ]);

    $submission = ExerciseSubmission::query()->where('user_id', $owner->id)->with('attachments')->firstOrFail();
    $attachment = $submission->attachments->firstOrFail();

    $this->actingAs($otherStudent)
        ->get(route('course.exercises.attachments.download', [$submission, $attachment]))
        ->assertForbidden();
});

test('deleted exercise attachment is not downloadable by student', function () {
    [$user] = createActiveStudent();
    $admin = User::factory()->admin()->create();

    $this->actingAs($user)->post(route('course.exercises.store'), [
        'title' => 'فایل حذف‌شده',
        'attachments' => [
            UploadedFile::fake()->image('deleted.png'),
        ],
    ]);

    $submission = ExerciseSubmission::query()->with('attachments')->firstOrFail();
    $attachment = $submission->attachments->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('admin.exercise-submissions.attachments.destroy', [$submission, $attachment]))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('course.exercises.attachments.download', [$submission, $attachment->fresh()]))
        ->assertNotFound();
});

test('student sees teacher feedback attachments on exercises index', function () {
    [$user] = createActiveStudent();

    $submission = ExerciseSubmission::factory()->forUser($user)->create([
        'title' => 'تمرین با فایل استاد',
    ]);

    $path = 'exercise-submission-feedback/'.$submission->id.'/notes.pdf';

    ExerciseSubmissionFeedbackAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'notes.pdf',
        'path' => $path,
        'size_bytes' => 2048,
    ]);

    Storage::disk('local')->put($path, 'pdf-content');

    $this->actingAs($user)
        ->get(route('course.exercises.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('submissions.0.feedbackAttachments', 1)
            ->where('submissions.0.feedbackAttachments.0.originalName', 'notes.pdf'));
});

test('student can download own feedback attachment', function () {
    [$user] = createActiveStudent();

    $submission = ExerciseSubmission::factory()->forUser($user)->create([
        'title' => 'تمرین دانلود فایل استاد',
    ]);

    $path = 'exercise-submission-feedback/'.$submission->id.'/notes.pdf';

    $attachment = ExerciseSubmissionFeedbackAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'notes.pdf',
        'path' => $path,
    ]);

    Storage::disk('local')->put($path, 'pdf-content');

    $this->actingAs($user)
        ->get(route('course.exercises.feedback-attachments.download', [$submission, $attachment]))
        ->assertOk();
});

test('student cannot download another students feedback attachment', function () {
    [$owner] = createActiveStudent();
    [$other] = createActiveStudent();

    $submission = ExerciseSubmission::factory()->forUser($owner)->create([
        'title' => 'تمرین خصوصی',
    ]);

    $path = 'exercise-submission-feedback/'.$submission->id.'/private.pdf';

    $attachment = ExerciseSubmissionFeedbackAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'private.pdf',
        'path' => $path,
    ]);

    Storage::disk('local')->put($path, 'content');

    $this->actingAs($other)
        ->get(route('course.exercises.feedback-attachments.download', [$submission, $attachment]))
        ->assertForbidden();
});

test('guest cannot download feedback attachment', function () {
    $user = User::factory()->create();

    $submission = ExerciseSubmission::factory()->forUser($user)->create([
        'title' => 'تمرین',
    ]);

    $attachment = ExerciseSubmissionFeedbackAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'notes.pdf',
        'path' => 'exercise-submission-feedback/'.$submission->id.'/notes.pdf',
    ]);

    $this->get(route('course.exercises.feedback-attachments.download', [$submission, $attachment]))
        ->assertRedirect(route('login'));
});

test('deleted feedback attachment is not shown to student', function () {
    [$user] = createActiveStudent();

    $submission = ExerciseSubmission::factory()->forUser($user)->create([
        'title' => 'تمرین حذف‌شده',
    ]);

    $path = 'exercise-submission-feedback/'.$submission->id.'/deleted.pdf';

    ExerciseSubmissionFeedbackAttachment::factory()->forSubmission($submission)->create([
        'original_name' => 'deleted.pdf',
        'path' => $path,
        'deleted_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('course.exercises.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('submissions.0.feedbackAttachments', []));
});

test('student sees awarded XP on approved submission', function () {
    [$user] = createActiveStudent();
    $admin = User::factory()->admin()->create();

    $submission = ExerciseSubmission::factory()->forUser($user)->create([
        'title' => 'تمرین XP هنرجو',
        'status' => ExerciseSubmissionStatus::Approved,
    ]);

    StudentXpEvent::create([
        'user_id' => $user->id,
        'source_type' => 'exercise_submission',
        'source_id' => $submission->id,
        'points' => 150,
        'awarded_by' => $admin->id,
        'awarded_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('course.exercises.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('submissions.0.awardedXp', 150));
});

test('student does not see XP on non-approved submission', function () {
    [$user] = createActiveStudent();

    ExerciseSubmission::factory()->forUser($user)->create([
        'title' => 'تمرین در انتظار',
        'status' => ExerciseSubmissionStatus::NeedsRevision,
    ]);

    $this->actingAs($user)
        ->get(route('course.exercises.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('submissions.0.awardedXp', null));
});

test('course home props include total XP for user', function () {
    [$user] = createActiveStudent();
    $admin = User::factory()->admin()->create();

    $first = ExerciseSubmission::factory()->forUser($user)->create(['title' => 'تمرین اول', 'status' => ExerciseSubmissionStatus::Approved]);
    $second = ExerciseSubmission::factory()->forUser($user)->create(['title' => 'تمرین دوم', 'status' => ExerciseSubmissionStatus::Approved]);

    StudentXpEvent::create(['user_id' => $user->id, 'source_type' => 'exercise_submission', 'source_id' => $first->id, 'points' => 150, 'awarded_by' => $admin->id, 'awarded_at' => now()]);
    StudentXpEvent::create(['user_id' => $user->id, 'source_type' => 'exercise_submission', 'source_id' => $second->id, 'points' => 250, 'awarded_by' => $admin->id, 'awarded_at' => now()]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('progress.totalXp', 400));
});

test('rich story text is stored and displayed safely', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->post(route('course.exercises.store'), [
            'title' => 'داستان من',
            'description' => "**شروع**\n- صحنه اول\n<script>alert(1)</script>",
            'attachments' => [
                UploadedFile::fake()->image('story.png'),
            ],
        ])
        ->assertRedirect(route('course.exercises.index'));

    $submission = ExerciseSubmission::query()->firstOrFail();

    expect($submission->description)
        ->toContain('**شروع**')
        ->not->toContain('<script>');

    $this->actingAs($user)
        ->get(route('course.exercises.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('submissions.0.descriptionPreview', fn (string $preview): bool => str_contains($preview, 'شروع'))
            ->where('submissions.0.descriptionHtml', fn (string $html): bool => str_contains($html, '<strong>شروع</strong>')
                && ! str_contains($html, '<script>')));
});
