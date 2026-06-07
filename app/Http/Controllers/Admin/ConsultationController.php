<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConsultationRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminConsultationRequestRequest;
use App\Models\ConsultationRequest;
use App\Services\Admin\AdminConsultationListService;
use App\Services\Admin\AdminConsultationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationController extends Controller
{
    public function __construct(
        private readonly AdminConsultationListService $consultationList,
        private readonly AdminConsultationService $consultations,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $search = $request->string('q')->toString();

        return Inertia::render('admin/consultations/index', $this->consultationList->listForAdmin(
            $status !== '' ? $status : null,
            $search !== '' ? $search : null,
        ));
    }

    public function update(
        UpdateAdminConsultationRequestRequest $request,
        ConsultationRequest $consultation,
    ): RedirectResponse {
        $validated = $request->validated();

        $this->consultations->update($consultation, [
            'status' => ConsultationRequestStatus::from($validated['status']),
            'admin_note' => $validated['admin_note'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'درخواست مشاوره به‌روزرسانی شد.']);

        return redirect()->back();
    }
}
