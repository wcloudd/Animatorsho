<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExerciseSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminExerciseSubmissionRequest;
use App\Models\ExerciseSubmission;
use App\Services\Admin\AdminExerciseSubmissionListService;
use App\Services\Admin\AdminExerciseSubmissionService;
use App\Services\Course\ExerciseSubmissionAttachmentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExerciseSubmissionController extends Controller
{
    public function __construct(
        private readonly AdminExerciseSubmissionListService $submissionList,
        private readonly AdminExerciseSubmissionService $submissions,
        private readonly ExerciseSubmissionAttachmentStorageService $attachments,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $search = $request->string('q')->toString();

        return Inertia::render('admin/exercise-submissions/index', $this->submissionList->listForAdmin(
            $status !== '' ? $status : null,
            $search !== '' ? $search : null,
        ));
    }

    public function show(ExerciseSubmission $exerciseSubmission): Response
    {
        return Inertia::render(
            'admin/exercise-submissions/show',
            $this->submissions->showForAdmin($exerciseSubmission),
        );
    }

    public function update(
        UpdateAdminExerciseSubmissionRequest $request,
        ExerciseSubmission $exerciseSubmission,
    ): RedirectResponse {
        $validated = $request->validated();

        $this->submissions->review($exerciseSubmission, $request->user(), [
            'status' => ExerciseSubmissionStatus::from($validated['status']),
            'admin_feedback' => $validated['admin_feedback'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'بررسی تمرین ذخیره شد.']);

        return redirect()->back();
    }

    public function attachment(ExerciseSubmission $exerciseSubmission): StreamedResponse
    {
        if (! $exerciseSubmission->hasActiveAttachment()) {
            abort(404);
        }

        return $this->attachments->downloadResponse($exerciseSubmission);
    }

    public function destroyAttachment(ExerciseSubmission $exerciseSubmission): RedirectResponse
    {
        $this->submissions->deleteAttachment($exerciseSubmission, auth()->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'فایل تمرین حذف شد.']);

        return redirect()->back();
    }
}
