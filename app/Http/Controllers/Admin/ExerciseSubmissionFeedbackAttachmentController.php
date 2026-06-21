<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExerciseSubmission;
use App\Models\ExerciseSubmissionFeedbackAttachment;
use App\Services\Course\ExerciseSubmissionFeedbackStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExerciseSubmissionFeedbackAttachmentController extends Controller
{
    public function __construct(
        private readonly ExerciseSubmissionFeedbackStorageService $service,
    ) {}

    public function store(Request $request, ExerciseSubmission $exerciseSubmission): RedirectResponse
    {
        $allowedExtensions = config('exercise_submissions.attachment_extensions', []);
        $maxKb = (int) config('exercise_submissions.attachment_max_kb', 5120);
        $maxFeedback = (int) config('exercise_submissions.max_feedback_attachments_per_submission', 3);

        $request->validate([
            'feedback_files' => ['required', 'array', 'min:1', 'max:'.$maxFeedback],
            'feedback_files.*' => [
                'required',
                'file',
                'max:'.$maxKb,
                'mimes:'.implode(',', $allowedExtensions),
            ],
        ]);

        try {
            $this->service->storeMany(
                $exerciseSubmission,
                $request->user(),
                $request->file('feedback_files', []),
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['feedback_files' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'فایل‌های استاد آپلود شد.']);

        return redirect()->back();
    }

    public function download(
        ExerciseSubmission $exerciseSubmission,
        ExerciseSubmissionFeedbackAttachment $feedbackAttachment,
    ): StreamedResponse {
        if ($feedbackAttachment->exercise_submission_id !== $exerciseSubmission->id) {
            abort(404);
        }

        return $this->service->downloadResponse($feedbackAttachment);
    }

    public function destroy(
        ExerciseSubmission $exerciseSubmission,
        ExerciseSubmissionFeedbackAttachment $feedbackAttachment,
    ): RedirectResponse {
        if ($feedbackAttachment->exercise_submission_id !== $exerciseSubmission->id) {
            abort(404);
        }

        $this->service->markDeleted($feedbackAttachment, auth()->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'فایل استاد حذف شد.']);

        return redirect()->back();
    }
}
