<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Services\Admin\AdminSiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingsController extends Controller
{
    public function __construct(
        private readonly AdminSiteSettingsService $siteSettings,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/site-settings/index', $this->siteSettings->indexForAdmin());
    }

    public function update(UpdateSiteSettingsRequest $request): RedirectResponse
    {
        $this->siteSettings->updateSettings($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تنظیمات سایت ذخیره شد.']);

        return redirect()->route('admin.site-settings.index');
    }
}
