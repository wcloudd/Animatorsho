<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportTicketMessageRequest;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Services\SupportTicketAttachmentStorageService;
use App\Services\SupportTicketService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $tickets,
        private readonly SupportTicketAttachmentStorageService $attachments,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('support/index', $this->tickets->listForUser($request->user()));
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        try {
            $ticket = $this->tickets->createForUser(
                $request->user(),
                $request->validated(),
                $request->file('attachment'),
            );
        } catch (InvalidArgumentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return redirect()->back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'پیام شما ثبت شد.']);

        return redirect()->route('support.tickets.show', $ticket);
    }

    public function show(Request $request, SupportTicket $ticket): Response
    {
        try {
            $props = $this->tickets->showForUser($ticket, $request->user());
        } catch (AuthorizationException) {
            abort(403);
        }

        return Inertia::render('support/show', $props);
    }

    public function storeMessage(StoreSupportTicketMessageRequest $request, SupportTicket $ticket): RedirectResponse
    {
        try {
            $this->tickets->replyAsUser(
                $ticket,
                $request->user(),
                $request->validated('body'),
                $request->file('attachment'),
            );
        } catch (AuthorizationException) {
            abort(403);
        } catch (InvalidArgumentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return redirect()->back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'پاسخ شما ثبت شد.']);

        return redirect()->back();
    }

    public function downloadAttachment(
        Request $request,
        SupportTicket $ticket,
        SupportTicketAttachment $attachment,
    ): StreamedResponse {
        try {
            $this->tickets->downloadAttachmentForUser($ticket, $attachment, $request->user());
        } catch (AuthorizationException) {
            abort(403);
        }

        return $this->attachments->streamResponse($attachment);
    }
}
