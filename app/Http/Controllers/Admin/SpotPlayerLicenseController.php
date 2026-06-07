<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivateSpotPlayerLicenseRequest;
use App\Models\SpotPlayerLicense;
use App\Services\Admin\AdminSpotPlayerLicenseListService;
use App\Services\Admin\AdminSpotPlayerLicenseService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SpotPlayerLicenseController extends Controller
{
    public function __construct(
        private readonly AdminSpotPlayerLicenseListService $licenseList,
        private readonly AdminSpotPlayerLicenseService $licenses,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/licenses/index', $this->licenseList->listForAdmin());
    }

    public function activate(ActivateSpotPlayerLicenseRequest $request, SpotPlayerLicense $license): RedirectResponse
    {
        $this->licenses->activate($license, $request->validated('license_key'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'لایسنس با موفقیت فعال شد.']);

        return redirect()->back();
    }

    public function revoke(SpotPlayerLicense $license): RedirectResponse
    {
        $this->licenses->revoke($license);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'لایسنس لغو شد.']);

        return redirect()->back();
    }
}
