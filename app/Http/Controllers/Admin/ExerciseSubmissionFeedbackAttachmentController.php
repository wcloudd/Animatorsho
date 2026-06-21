<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExerciseSubmission;
use App\Models\ExerciseSubmissionFeedbackAttachment;
use App\Services\Course\ExerciseSubmissionFeedbackStorageService;
use App\Services\StudentNotificationService;
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
        private readonly StudentNotificationService $notifications,
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

        $files = $request->file('feedback_files', []);

        try {
            $this->service->storeMany($exerciseSubmission, $request->user(), $files);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['feedback_files' => $e->getMessage()]);
        }

        $student = $exerciseSubmission->user;

        if ($student !== null) {
            $count = is_array($files) ? count($files) : 0;
            $body = $count > 1
                ? 'استاد چند فایل برای تمرینت ارسال کرد.'
                : 'استاد یک فایل برای تمرینت ارسال کرد.';

            $this->notifications->upsertForSource(
                $student,
                StudentNotificationService::TYPE_TEACHER_FEEDBACK_ATTACHMENT_ADDED,
                'exercise_submission',
                $exerciseSubmission->id,
                ['title' => 'فایل استاد برای تمرینت اضافه شد', 'body' => $body, 'action_url' => route('course.exercises.index')],
            );
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
