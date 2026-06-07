<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSmsSettingsRequest;
use App\Http\Requests\Admin\UpdateSmsTemplateRequest;
use App\Models\SmsTemplate;
use App\Services\Admin\AdminSmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SmsController extends Controller
{
    public function __construct(
        private readonly AdminSmsService $sms,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/sms/index', $this->sms->indexForAdmin());
    }

    public function logs(Request $request): Response
    {
        $search = $request->string('q')->toString();

        return Inertia::render('admin/sms/logs', $this->sms->logsForAdmin(
            $search !== '' ? $search : null,
        ));
    }

    public function updateSettings(UpdateSmsSettingsRequest $request): RedirectResponse
    {
        $this->sms->updateSettings($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تنظیمات پیامک ذخیره شد.']);

        return redirect()->route('admin.sms.index');
    }

    public function updateTemplate(UpdateSmsTemplateRequest $request, SmsTemplate $template): RedirectResponse
    {
        $this->sms->updateTemplate($template, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'قالب پیامک به‌روزرسانی شد.']);

        return redirect()->route('admin.sms.index');
    }
}
