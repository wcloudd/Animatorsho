<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseUpdateRequest;
use App\Http\Requests\Admin\UpdateCourseUpdateRequest;
use App\Models\CourseUpdate;
use App\Services\Admin\AdminCourseUpdateService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseUpdateController extends Controller
{
    public function __construct(
        private readonly AdminCourseUpdateService $courseUpdates,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/course-updates/index', $this->courseUpdates->listForAdmin());
    }

    public function create(): Response
    {
        return Inertia::render('admin/course-updates/create', $this->courseUpdates->createFormProps());
    }

    public function store(StoreCourseUpdateRequest $request): RedirectResponse
    {
        $update = $this->courseUpdates->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'آپدیت دوره با موفقیت ایجاد شد.']);

        return redirect()->route('admin.course-updates.edit', $update);
    }

    public function edit(CourseUpdate $courseUpdate): Response
    {
        return Inertia::render('admin/course-updates/edit', $this->courseUpdates->editFormProps($courseUpdate));
    }

    public function update(UpdateCourseUpdateRequest $request, CourseUpdate $courseUpdate): RedirectResponse
    {
        $this->courseUpdates->update($courseUpdate, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'آپدیت دوره با موفقیت به‌روزرسانی شد.']);

        return redirect()->route('admin.course-updates.index');
    }
}
