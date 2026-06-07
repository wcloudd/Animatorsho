<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultationRequestRequest;
use App\Models\User;
use App\Services\ConsultationRequestService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationController extends Controller
{
    public function __construct(
        private readonly ConsultationRequestService $consultations,
    ) {}

    public function index(): Response
    {
        return Inertia::render('consultation/index');
    }

    public function store(StoreConsultationRequestRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->consultations->create(
            $user,
            $request->validated(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'درخواست مشاوره شما ثبت شد. پشتیبانی انیماتورشو با شما تماس می‌گیرد.',
        ]);

        return redirect()->route('consultation');
    }
}
