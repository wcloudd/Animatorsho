<?php

namespace App\Http\Controllers;

use App\Services\Course\CourseAccessService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseHomeController extends Controller
{
    public function index(CourseAccessService $courseAccess): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user === null || ! $courseAccess->userHasActiveAccess($user)) {
            return redirect()
                ->route('profile')
                ->with(
                    'status',
                    'دسترسی فعالی برای دوره ندارید. وضعیت ثبت‌نام و لایسنس را در پروفایل بررسی کنید.',
                );
        }

        return Inertia::render(
            'animatorsho/course-home',
            $courseAccess->courseHomePropsForUser($user),
        );
    }
}
