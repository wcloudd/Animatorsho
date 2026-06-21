<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentMedalAwardRequest;
use App\Models\StudentMedalAward;
use App\Models\User;
use App\Services\StudentMedalService;
use App\Services\StudentNotificationService;
use App\Support\JalaliDateFormatter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentMedalController extends Controller
{
    public function __construct(
        private readonly StudentMedalService $medals,
        private readonly StudentNotificationService $notifications,
    ) {}

    public function index(): Response
    {
        $recentAwards = $this->medals->recentAwards()->map(fn (StudentMedalAward $award): array => [
            'id' => $award->id,
            'studentName' => $award->user->name,
            'studentMobile' => $award->user->mobile,
            'medalKey' => $award->medal_key,
            'medalTitle' => StudentMedalService::MEDALS[$award->medal_key] ?? $award->medal_key,
            'awardedAtLabel' => JalaliDateFormatter::publishedAtLabel($award->awarded_at),
            'awardedByName' => $award->awarder?->name ?? '—',
            'note' => $award->note,
        ])->values()->all();

        return Inertia::render('admin/student-medals/index', [
            'medals' => $this->medals->medals(),
            'recentAwards' => $recentAwards,
        ]);
    }

    public function store(StoreStudentMedalAwardRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $admin = $request->user();

        if ($admin === null) {
            abort(403);
        }

        $student = User::query()->findOrFail((int) $validated['user_id']);

        $this->medals->award(
            student: $student,
            medalKey: $validated['medal_key'],
            admin: $admin,
            note: isset($validated['note']) && $validated['note'] !== '' ? $validated['note'] : null,
        );

        $medalTitle = StudentMedalService::MEDALS[$validated['medal_key']] ?? '';
        $this->notifications->create($student, [
            'type' => StudentNotificationService::TYPE_MEDAL_AWARDED,
            'title' => 'مدال جدید گرفتی',
            'body' => "مدال {$medalTitle} برای تو ثبت شد.",
            'action_url' => route('course.home'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'مدال با موفقیت اعطا شد.']);

        return redirect()->route('admin.student-medals.index');
    }

    public function destroy(StudentMedalAward $studentMedalAward): RedirectResponse
    {
        $this->medals->revoke($studentMedalAward);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'مدال لغو شد.']);

        return redirect()->route('admin.student-medals.index');
    }
}
