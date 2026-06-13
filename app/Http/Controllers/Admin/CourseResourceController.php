<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseResourceRequest;
use App\Http\Requests\Admin\UpdateCourseResourceRequest;
use App\Models\CourseResource;
use App\Services\Admin\AdminCourseResourceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseResourceController extends Controller
{
    public function __construct(
        private readonly AdminCourseResourceService $courseResources,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/course-resources/index', $this->courseResources->listForAdmin());
    }

    public function create(): Response
    {
        return Inertia::render('admin/course-resources/create', $this->courseResources->createFormProps());
    }

    public function store(StoreCourseResourceRequest $request): RedirectResponse
    {
        $resource = $this->courseResources->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'منبع دوره با موفقیت ایجاد شد.']);

        return redirect()->route('admin.course-resources.edit', $resource);
    }

    public function edit(CourseResource $courseResource): Response
    {
        return Inertia::render('admin/course-resources/edit', $this->courseResources->editFormProps($courseResource));
    }

    public function update(UpdateCourseResourceRequest $request, CourseResource $courseResource): RedirectResponse
    {
        $this->courseResources->update($courseResource, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'منبع دوره با موفقیت به‌روزرسانی شد.']);

        return redirect()->route('admin.course-resources.index');
    }
}
