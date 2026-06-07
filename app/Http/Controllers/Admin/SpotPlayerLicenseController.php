<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SpotPlayerLicenseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivateSpotPlayerLicenseRequest;
use App\Models\SpotPlayerLicense;
use App\Services\Admin\AdminSpotPlayerLicenseListService;
use App\Services\Admin\AdminSpotPlayerLicenseService;
use App\Services\SpotPlayer\SpotPlayerApiProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpotPlayerLicenseController extends Controller
{
    public function __construct(
        private readonly AdminSpotPlayerLicenseListService $licenseList,
        private readonly AdminSpotPlayerLicenseService $licenses,
        private readonly SpotPlayerApiProvisioningService $spotPlayerApi,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('q')->toString();
        $focus = $request->has('focus') ? $request->integer('focus') : null;

        return Inertia::render('admin/licenses/index', $this->licenseList->listForAdmin(
            $search !== '' ? $search : null,
            $focus,
        ));
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

    public function retryProvision(SpotPlayerLicense $license): RedirectResponse
    {
        $license = $this->spotPlayerApi->attemptForLicense($license);
        $license->refresh();

        if ($license->status === SpotPlayerLicenseStatus::Active) {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'لایسنس SpotPlayer با موفقیت فعال شد.']);
        } else {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'درخواست ارسال شد اما لایسنس هنوز فعال نشد. جزئیات را در همین صفحه بررسی کنید.']);
        }

        return redirect()->back();
    }
}
