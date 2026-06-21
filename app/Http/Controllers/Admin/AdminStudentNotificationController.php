<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminStudentNotificationController extends Controller
{
    public function __construct(
        private readonly StudentNotificationService $notifications,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/student-notifications/index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'action_url' => ['nullable', 'string', 'max:500'],
        ]);

        $admin = $request->user();

        if ($admin === null) {
            abort(403);
        }

        $student = User::query()->findOrFail((int) $validated['user_id']);

        $this->notifications->createAdminMessage(
            student: $student,
            title: $validated['title'],
            body: $validated['body'],
            actionUrl: isset($validated['action_url']) && $validated['action_url'] !== ''
                ? $validated['action_url']
                : null,
            admin: $admin,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'پیام برای هنرجو ارسال شد.']);

        return redirect()->route('admin.student-notifications.index');
    }
}
