<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminSupportTicketMessageRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Services\Admin\AdminSupportTicketListService;
use App\Services\Admin\AdminSupportTicketService;
use App\Services\SupportTicketAttachmentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketController extends Controller
{
    public function __construct(
        private readonly AdminSupportTicketListService $ticketList,
        private readonly AdminSupportTicketService $tickets,
        private readonly SupportTicketAttachmentStorageService $attachments,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $category = $request->string('category')->toString();

        return Inertia::render('admin/support/index', $this->ticketList->listForAdmin(
            $status !== '' ? $status : null,
            $category !== '' ? $category : null,
        ));
    }

    public function show(SupportTicket $ticket): Response
    {
        return Inertia::render('admin/support/show', $this->tickets->showForAdmin($ticket));
    }

    public function storeMessage(StoreAdminSupportTicketMessageRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->tickets->replyAsAdmin(
                $ticket,
                $request->user(),
                $validated['body'],
                (bool) ($validated['waiting_for_user'] ?? false),
                $request->file('attachment'),
            );
        } catch (InvalidArgumentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return redirect()->back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'پاسخ ثبت شد.']);

        return redirect()->back();
    }

    public function downloadAttachment(
        SupportTicket $ticket,
        SupportTicketAttachment $attachment,
    ): StreamedResponse {
        $this->tickets->verifyAttachmentForTicket($ticket, $attachment);

        return $this->attachments->streamResponse($attachment);
    }

    public function close(SupportTicket $ticket): RedirectResponse
    {
        $this->tickets->close($ticket);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تیکت بسته شد.']);

        return redirect()->back();
    }

    public function reopen(SupportTicket $ticket): RedirectResponse
    {
        $this->tickets->reopen($ticket);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تیکت دوباره باز شد.']);

        return redirect()->back();
    }
}
