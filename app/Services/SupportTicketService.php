<?php

namespace App\Services;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketMessageSenderType;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\Sms\SmsNotifier;
use App\Support\SupportTicketStatusLabels;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class SupportTicketService
{
    public function __construct(
        private readonly SmsNotifier $smsNotifier,
        private readonly SupportTicketAttachmentStorageService $attachments,
    ) {}

    /**
     * @return array{
     *     tickets: list<array{
     *         id: int,
     *         subject: string,
     *         status: string,
     *         statusValue: string,
     *         statusTone: string,
     *         category: string,
     *         categoryValue: string,
     *         createdAt: ?string
     *     }>,
     *     categoryOptions: list<array{value: string, label: string}>,
     *     helpNote: array{title: string, text: string, ctaLabel: string, ctaHref: string}
     * }
     */
    public function listForUser(User $user): array
    {
        $tickets = $user->supportTickets()
            ->latest()
            ->get()
            ->map(fn (SupportTicket $ticket) => $this->mapTicketListItem($ticket))
            ->all();

        return [
            'tickets' => $tickets,
            'categoryOptions' => SupportTicketStatusLabels::categoryOptions(),
            'helpNote' => $this->helpNote(),
        ];
    }

    /**
     * @param  array{subject: string, category: string, message: string}  $data
     */
    public function createForUser(User $user, array $data, ?UploadedFile $attachment = null): SupportTicket
    {
        $category = SupportTicketCategory::from($data['category']);
        $storedPath = null;

        try {
            $ticket = DB::transaction(function () use ($user, $data, $category, $attachment, &$storedPath): SupportTicket {
                $ticket = SupportTicket::query()->create([
                    'user_id' => $user->id,
                    'subject' => $data['subject'],
                    'category' => $category,
                    'status' => SupportTicketStatus::Open,
                    'customer_name' => $user->name,
                    'customer_mobile' => $this->resolveCustomerMobile($user),
                ]);

                $message = SupportTicketMessage::query()->create([
                    'support_ticket_id' => $ticket->id,
                    'sender_type' => SupportTicketMessageSenderType::User,
                    'user_id' => $user->id,
                    'body' => $data['message'],
                ]);

                if ($attachment !== null) {
                    $record = $this->attachments->store($message, $attachment);
                    $storedPath = $record->path;
                }

                return $ticket;
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                $this->attachments->delete($storedPath);
            }

            throw $exception;
        }

        $this->smsNotifier->notifySupportTicketCreatedAdmin($ticket);

        return $ticket;
    }

    /**
     * @return array{
     *     ticket: array{
     *         id: int,
     *         subject: string,
     *         status: string,
     *         statusValue: string,
     *         statusTone: string,
     *         category: string,
     *         categoryValue: string,
     *         createdAt: ?string,
     *         canReply: bool
     *     },
     *     messages: list<array<string, mixed>>
     * }
     */
    public function showForUser(SupportTicket $ticket, User $user): array
    {
        $this->ensureTicketOwner($ticket, $user);

        return [
            'ticket' => $this->mapTicketDetail($ticket),
            'messages' => $this->mapMessages($ticket),
        ];
    }

    public function replyAsUser(SupportTicket $ticket, User $user, string $body, ?UploadedFile $attachment = null): void
    {
        $this->ensureTicketOwner($ticket, $user);

        if ($ticket->isClosed()) {
            throw new InvalidArgumentException('این تیکت بسته شده و امکان پاسخ وجود ندارد.');
        }

        $storedPath = null;

        try {
            DB::transaction(function () use ($ticket, $user, $body, $attachment, &$storedPath): void {
                $message = SupportTicketMessage::query()->create([
                    'support_ticket_id' => $ticket->id,
                    'sender_type' => SupportTicketMessageSenderType::User,
                    'user_id' => $user->id,
                    'body' => $body,
                ]);

                if ($attachment !== null) {
                    $record = $this->attachments->store($message, $attachment);
                    $storedPath = $record->path;
                }

                $ticket->update([
                    'status' => SupportTicketStatus::Open,
                ]);
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                $this->attachments->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function downloadAttachmentForUser(
        SupportTicket $ticket,
        SupportTicketAttachment $attachment,
        User $user,
    ): void {
        $this->ensureTicketOwner($ticket, $user);

        if (! $this->attachments->belongsToTicket($attachment, $ticket->id)) {
            abort(404);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function mapMessages(SupportTicket $ticket): array
    {
        return $ticket->messages()
            ->with('attachment')
            ->oldest()
            ->get()
            ->map(fn (SupportTicketMessage $message) => $this->mapMessage($ticket, $message))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMessage(SupportTicket $ticket, SupportTicketMessage $message): array
    {
        $attachment = $message->attachment;

        return [
            'id' => $message->id,
            'body' => $message->body,
            'senderType' => $message->sender_type->value,
            'senderLabel' => $this->senderLabel($message),
            'createdAt' => $message->created_at?->format('Y/m/d H:i'),
            'attachment' => $attachment !== null
                ? $this->attachments->toMessageArray(
                    $attachment,
                    route('support.tickets.attachments.download', [$ticket, $attachment]),
                )
                : null,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     subject: string,
     *     status: string,
     *     statusValue: string,
     *     statusTone: string,
     *     category: string,
     *     categoryValue: string,
     *     createdAt: ?string,
     *     canReply: bool
     * }
     */
    public function mapTicketDetail(SupportTicket $ticket): array
    {
        return [
            ...$this->mapTicketListItem($ticket),
            'canReply' => ! $ticket->isClosed(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     subject: string,
     *     status: string,
     *     statusValue: string,
     *     statusTone: string,
     *     category: string,
     *     categoryValue: string,
     *     createdAt: ?string
     * }
     */
    private function mapTicketListItem(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => SupportTicketStatusLabels::status($ticket->status),
            'statusValue' => $ticket->status->value,
            'statusTone' => SupportTicketStatusLabels::statusTone($ticket->status),
            'category' => SupportTicketStatusLabels::category($ticket->category),
            'categoryValue' => $ticket->category->value,
            'createdAt' => $ticket->created_at?->format('Y/m/d H:i'),
        ];
    }

    private function senderLabel(SupportTicketMessage $message): string
    {
        return match ($message->sender_type) {
            SupportTicketMessageSenderType::User => 'شما',
            SupportTicketMessageSenderType::Admin => 'پشتیبانی',
            SupportTicketMessageSenderType::System => 'سیستم',
        };
    }

    private function resolveCustomerMobile(User $user): ?string
    {
        $mobile = $user->orders()->latest()->value('customer_mobile');

        return is_string($mobile) && $mobile !== '' ? $mobile : null;
    }

    private function ensureTicketOwner(SupportTicket $ticket, User $user): void
    {
        if ($ticket->user_id !== $user->id) {
            throw new AuthorizationException;
        }
    }

    /**
     * @return array{title: string, text: string, ctaLabel: string, ctaHref: string}
     */
    private function helpNote(): array
    {
        return [
            'title' => 'قبل از ارسال پیام',
            'text' => 'اگر سوالت عمومی است، بخش سوالات پرتکرار صفحه معرفی دوره را هم ببین. برای موارد مربوط به پرداخت، سفارش یا لایسنس، پیام پشتیبانی بهترین مسیر پیگیری است.',
            'ctaLabel' => 'رفتن به سوالات پرتکرار',
            'ctaHref' => '/#faq',
        ];
    }
}
