<?php

namespace App\Http\Controllers;

use App\Services\Course\CourseAccessService;
use App\Services\StudentMedalService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseMedalsController extends Controller
{
    public function index(CourseAccessService $courseAccess, StudentMedalService $medalService): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user === null || ! $courseAccess->userHasActiveAccess($user)) {
            return redirect()->route('course.home');
        }

        return Inertia::render('animatorsho/course-medals', [
            'medals' => $medalService->medalsPreviewForUser($user),
        ]);
    }
}
