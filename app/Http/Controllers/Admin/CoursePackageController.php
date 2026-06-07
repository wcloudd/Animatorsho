<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCoursePackageRequest;
use App\Models\CoursePackage;
use App\Services\Admin\AdminCoursePackageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CoursePackageController extends Controller
{
    public function __construct(
        private readonly AdminCoursePackageService $packages,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/packages/index', $this->packages->listForAdmin());
    }

    public function edit(CoursePackage $package): Response
    {
        return Inertia::render('admin/packages/edit', $this->packages->toEditProps($package));
    }

    public function update(UpdateCoursePackageRequest $request, CoursePackage $package): RedirectResponse
    {
        $this->packages->update($package, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'بسته با موفقیت به‌روزرسانی شد.']);

        return redirect()->route('admin.packages.index');
    }
}
