<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExerciseSubmissionRequest;
use App\Models\ExerciseSubmission;
use App\Services\Course\CourseAccessService;
use App\Services\Course\ExerciseSubmissionAttachmentStorageService;
use App\Services\Course\ExerciseSubmissionQueryService;
use App\Services\Course\ExerciseSubmissionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseExercisesController extends Controller
{
    public function __construct(
        private readonly ExerciseSubmissionAttachmentStorageService $attachments,
    ) {}

    public function index(
        CourseAccessService $courseAccess,
        ExerciseSubmissionQueryService $exerciseSubmissions,
    ): Response|RedirectResponse {
        $user = auth()->user();

        if ($user === null || ! $courseAccess->userHasActiveAccess($user)) {
            return $this->redirectWithoutAccess();
        }

        return Inertia::render(
            'animatorsho/course-exercises',
            $exerciseSubmissions->indexForUser($user),
        );
    }

    public function create(CourseAccessService $courseAccess): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user === null || ! $courseAccess->userHasActiveAccess($user)) {
            return $this->redirectWithoutAccess();
        }

        return Inertia::render('animatorsho/course-exercises-create', [
            'storeUrl' => route('course.exercises.store'),
            'indexUrl' => route('course.exercises.index'),
            'maxAttachmentKb' => (int) config('exercise_submissions.attachment_max_kb', 5120),
        ]);
    }

    public function store(
        StoreExerciseSubmissionRequest $request,
        CourseAccessService $courseAccess,
        ExerciseSubmissionService $exerciseSubmissions,
    ): RedirectResponse {
        $user = $request->user();

        if ($user === null || ! $courseAccess->userHasActiveAccess($user)) {
            return $this->redirectWithoutAccess();
        }

        $validated = $request->validated();

        try {
            $exerciseSubmissions->storeForUser($user, [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'submission_url' => $validated['submission_url'] ?? null,
                'file_path' => $validated['file_path'] ?? null,
                'attachment' => $request->file('attachment'),
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['attachment' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تمرین با موفقیت ارسال شد.']);

        return redirect()->route('course.exercises.index');
    }

    public function attachment(
        CourseAccessService $courseAccess,
        ExerciseSubmission $exerciseSubmission,
    ): StreamedResponse|RedirectResponse {
        $user = auth()->user();

        if ($user === null || ! $courseAccess->userHasActiveAccess($user)) {
            return $this->redirectWithoutAccess();
        }

        if ($exerciseSubmission->user_id !== $user->id) {
            abort(403);
        }

        if (! $exerciseSubmission->hasActiveAttachment()) {
            abort(404);
        }

        return $this->attachments->downloadResponse($exerciseSubmission);
    }

    private function redirectWithoutAccess(): RedirectResponse
    {
        return redirect()
            ->route('profile')
            ->with(
                'status',
                'دسترسی فعالی برای دوره ندارید. وضعیت ثبت‌نام و لایسنس را در پروفایل بررسی کنید.',
            );
    }
}
