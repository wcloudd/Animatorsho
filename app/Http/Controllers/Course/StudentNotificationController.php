<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Models\StudentNotification;
use App\Services\StudentNotificationService;
use Illuminate\Http\RedirectResponse;

class StudentNotificationController extends Controller
{
    public function __construct(
        private readonly StudentNotificationService $notifications,
    ) {}

    public function markRead(StudentNotification $studentNotification): RedirectResponse
    {
        $user = auth()->user();

        if ($user === null || $studentNotification->user_id !== $user->id) {
            abort(403);
        }

        $this->notifications->markRead($studentNotification);

        return redirect()->back();
    }

    public function markAllRead(): RedirectResponse
    {
        $user = auth()->user();

        if ($user === null) {
            abort(403);
        }

        $this->notifications->markAllReadForUser($user);

        return redirect()->back();
    }
}
