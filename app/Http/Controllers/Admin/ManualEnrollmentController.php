<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExternalEnrollmentSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManualEnrollmentRequest;
use App\Models\CoursePackage;
use App\Services\Admin\AdminManualEnrollmentListService;
use App\Services\Admin\AdminManualEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ManualEnrollmentController extends Controller
{
    public function __construct(
        private readonly AdminManualEnrollmentListService $list,
        private readonly AdminManualEnrollmentService $enrollment,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/manual-enrollments/index', $this->list->formData());
    }

    public function store(StoreManualEnrollmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $admin = $request->user();

        if ($admin === null) {
            abort(403);
        }

        $package = CoursePackage::query()
            ->where('is_active', true)
            ->findOrFail((int) $validated['course_package_id']);

        $source = ExternalEnrollmentSource::from($validated['source']);

        $result = $this->enrollment->grant(
            admin: $admin,
            customerName: $validated['customer_name'],
            userLookup: $validated['user_lookup'] ?? null,
            customerMobile: $validated['customer_mobile'] ?? null,
            package: $package,
            source: $source,
            note: $validated['admin_note'] ?? null,
            licenseKey: $validated['license_key'] ?? null,
        );

        if ($result->licenseActive) {
            $message = $result->userCreated
                ? 'دسترسی فعال ثبت شد. کاربر می‌تواند با ورود OTP وارد شود و داشبورد هنرجو برایش باز است.'
                : 'دسترسی فعال برای کاربر موجود ثبت شد و داشبورد هنرجو برایش باز است.';
        } else {
            $message = $result->userCreated
                ? 'دسترسی در حالت انتظار ثبت شد؛ تا فعال شدن لایسنس، داشبورد هنرجو برای کاربر باز نمی‌شود. کاربر می‌تواند با ورود OTP وارد شود.'
                : 'دسترسی در حالت انتظار ثبت شد؛ تا فعال شدن لایسنس، داشبورد هنرجو برای کاربر باز نمی‌شود.';
        }

        Inertia::flash('toast', [
            'type' => $result->licenseActive ? 'success' : 'warning',
            'message' => $message,
        ]);

        return redirect()->route('admin.manual-enrollments.index');
    }
}
