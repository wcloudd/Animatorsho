<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminInstallmentListService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstallmentController extends Controller
{
    public function __construct(
        private readonly AdminInstallmentListService $installments,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $search = $request->string('q')->toString();
        $focus = $request->has('focus') ? $request->integer('focus') : null;

        return Inertia::render('admin/installments/index', $this->installments->listForAdmin(
            $status !== '' ? $status : null,
            $search !== '' ? $search : null,
            $focus,
        ));
    }
}
