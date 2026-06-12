<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminSecurityEventListService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityEventController extends Controller
{
    public function __construct(
        private readonly AdminSecurityEventListService $securityEvents,
    ) {}

    public function index(Request $request): Response
    {
        $event = $request->string('event')->toString();
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $search = $request->string('q')->toString();
        $userId = $request->has('user_id') ? $request->integer('user_id') : null;

        return Inertia::render('admin/security-events/index', $this->securityEvents->listForAdmin(
            $event !== '' ? $event : null,
            $from !== '' ? $from : null,
            $to !== '' ? $to : null,
            $userId,
            $search !== '' ? $search : null,
        ));
    }
}
