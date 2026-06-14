<?php

use App\Enums\ExerciseSubmissionStatus;
use App\Models\CoursePackage;
use App\Models\ExerciseSubmission;
use App\Models\SpotPlayerLicense;
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

test('active student can submit exercise with valid url', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->post(route('course.exercises.store'), [
            'title' => 'تمرین پرش',
            'description' => 'اولین تمرین من',
            'submission_url' => 'https://www.aparat.com/v/example',
        ])
        ->assertRedirect(route('course.exercises.index'));

    $submission = ExerciseSubmission::query()->first();

    expect($submission)->not->toBeNull()
        ->and($submission->user_id)->toBe($user->id)
        ->and($submission->title)->toBe('تمرین پرش')
        ->and($submission->submission_url)->toBe('https://www.aparat.com/v/example')
        ->and($submission->status)->toBe(ExerciseSubmissionStatus::Submitted);
});

test('exercise submission validation requires url or file path', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->from(route('course.exercises.create'))
        ->post(route('course.exercises.store'), [
            'title' => 'تمرین بدون لینک',
            'description' => 'توضیح',
        ])
        ->assertRedirect(route('course.exercises.create'))
        ->assertSessionHasErrors('submission_url');

    expect(ExerciseSubmission::query()->count())->toBe(0);
});

test('student sees only own submissions on exercises index', function () {
    [$owner] = createActiveStudent();
    [$otherStudent] = createActiveStudent();

    ExerciseSubmission::factory()->forUser($owner)->create([
        'title' => 'تمرین من',
        'submission_url' => 'https://example.com/owner',
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
            ->where('submissions.0.title', 'تمرین من'));
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
            'submission_url' => "https://example.com/{$attempt}",
        ])->assertRedirect();
    }

    $this->actingAs($user)->post(route('course.exercises.store'), [
        'title' => 'تمرین سوم',
        'submission_url' => 'https://example.com/3',
    ])->assertStatus(429);
});

test('active student can upload allowed exercise file', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->post(route('course.exercises.store'), [
            'title' => 'تمرین فایل',
            'attachment' => UploadedFile::fake()->image('frame.png'),
        ])
        ->assertRedirect(route('course.exercises.index'));

    $submission = ExerciseSubmission::query()->first();

    expect($submission)->not->toBeNull()
        ->and($submission->attachment_original_name)->toBe('frame.png')
        ->and($submission->hasActiveAttachment())->toBeTrue();

    Storage::disk('local')->assertExists((string) $submission->attachment_path);
});

test('exercise file larger than 5mb is rejected', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->from(route('course.exercises.create'))
        ->post(route('course.exercises.store'), [
            'title' => 'فایل بزرگ',
            'attachment' => UploadedFile::fake()->create('large.zip', 5121, 'application/zip'),
        ])
        ->assertRedirect(route('course.exercises.create'))
        ->assertSessionHasErrors('attachment');

    expect(ExerciseSubmission::query()->count())->toBe(0);
});

test('invalid exercise file type is rejected', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->from(route('course.exercises.create'))
        ->post(route('course.exercises.store'), [
            'title' => 'فایل نامعتبر',
            'attachment' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
        ])
        ->assertRedirect(route('course.exercises.create'))
        ->assertSessionHasErrors('attachment');

    expect(ExerciseSubmission::query()->count())->toBe(0);
});

test('student can submit exercise with file only', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->post(route('course.exercises.store'), [
            'title' => 'فقط فایل',
            'attachment' => UploadedFile::fake()->create('story.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('course.exercises.index'));

    expect(ExerciseSubmission::query()->count())->toBe(1);
});

test('student can download own exercise attachment', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)->post(route('course.exercises.store'), [
        'title' => 'دانلود من',
        'attachment' => UploadedFile::fake()->image('mine.jpg'),
    ]);

    $submission = ExerciseSubmission::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('course.exercises.attachment', $submission))
        ->assertOk();
});

test('student cannot download another users exercise attachment', function () {
    [$owner] = createActiveStudent();
    [$otherStudent] = createActiveStudent();

    $this->actingAs($owner)->post(route('course.exercises.store'), [
        'title' => 'فایل خصوصی',
        'attachment' => UploadedFile::fake()->image('private.png'),
    ]);

    $submission = ExerciseSubmission::query()->where('user_id', $owner->id)->firstOrFail();

    $this->actingAs($otherStudent)
        ->get(route('course.exercises.attachment', $submission))
        ->assertForbidden();
});

test('deleted exercise attachment is not downloadable by student', function () {
    [$user] = createActiveStudent();
    $admin = User::factory()->admin()->create();

    $this->actingAs($user)->post(route('course.exercises.store'), [
        'title' => 'فایل حذف‌شده',
        'attachment' => UploadedFile::fake()->image('deleted.png'),
    ]);

    $submission = ExerciseSubmission::query()->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('admin.exercise-submissions.attachment.destroy', $submission))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('course.exercises.attachment', $submission))
        ->assertNotFound();
});

test('rich story text is stored and displayed safely', function () {
    [$user] = createActiveStudent();

    $this->actingAs($user)
        ->post(route('course.exercises.store'), [
            'title' => 'داستان من',
            'description' => "**شروع**\n- صحنه اول\n<script>alert(1)</script>",
            'submission_url' => 'https://example.com/story',
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
