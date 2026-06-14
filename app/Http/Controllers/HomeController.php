<?php

namespace App\Http\Controllers;

use App\Services\Course\CourseAccessService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(CourseAccessService $courseAccess, SeoService $seo): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user !== null && $courseAccess->userHasActiveAccess($user)) {
            return redirect()->route('course.home');
        }

        return Inertia::render('animatorsho/index', [
            'seo' => $seo->homePageSeoProps(),
        ]);
    }
}
