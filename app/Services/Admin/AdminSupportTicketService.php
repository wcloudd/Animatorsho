<?php

namespace App\Services\Admin;

use App\Enums\SupportTicketMessageSenderType;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\Sms\SmsNotifier;
use App\Services\SupportTicketAttachmentStorageService;
use App\Support\AdminStatusLabels;
use App\Support\ProfileStatusLabels;
use App\Support\SupportTicketStatusLabels;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class AdminSupportTicketService
{
    public function __construct(
        private readonly SmsNotifier $smsNotifier,
        private readonly SupportTicketAttachmentStorageService $attachments,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function showForAdmin(SupportTicket $ticket): array
    {
        $ticket->loadMissing(['user', 'messages.attachment']);

        $user = $ticket->user;

        return [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => SupportTicketStatusLabels::status($ticket->status),
                'statusValue' => $ticket->status->value,
                'statusTone' => SupportTicketStatusLabels::statusTone($ticket->status),
                'category' => SupportTicketStatusLabels::category($ticket->category),
                'categoryValue' => $ticket->category->value,
                'customerName' => $ticket->customer_name,
                'customerMobile' => $ticket->customer_mobile,
                'userName' => $user?->name ?? '',
                'userEmail' => $user?->email ?? '',
                'createdAt' => $ticket->created_at?->format('Y/m/d H:i'),
                'closedAt' => $ticket->closed_at?->format('Y/m/d H:i'),
                'isClosed' => $ticket->isClosed(),
            ],
            'messages' => $ticket->messages
                ->sortBy('created_at')
                ->values()
                ->map(fn (SupportTicketMessage $message) => $this->mapMessage($ticket, $message))
                ->all(),
            'recentOrders' => $this->recentOrders($user),
            'recentLicenses' => $this->recentLicenses($user),
        ];
    }

    public function replyAsAdmin(
        SupportTicket $ticket,
        User $admin,
        string $body,
        bool $waitingForUser = false,
        ?UploadedFile $attachment = null,
    ): void {
        if ($ticket->isClosed()) {
            throw new InvalidArgumentException('این تیکت بسته شده و امکان پاسخ وجود ندارد.');
        }

        $storedPath = null;

        try {
            DB::transaction(function () use ($ticket, $admin, $body, $waitingForUser, $attachment, &$storedPath): void {
                $message = SupportTicketMessage::query()->create([
                    'support_ticket_id' => $ticket->id,
                    'sender_type' => SupportTicketMessageSenderType::Admin,
                    'user_id' => $admin->id,
                    'body' => $body,
                ]);

                if ($attachment !== null) {
                    $record = $this->attachments->store($message, $attachment);
                    $storedPath = $record->path;
                }

                $ticket->update([
                    'status' => $waitingForUser
                        ? SupportTicketStatus::WaitingUser
                        : SupportTicketStatus::Answered,
                ]);
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                $this->attachments->delete($storedPath);
            }

            throw $exception;
        }

        $this->smsNotifier->notifySupportTicketRepliedUser($ticket->fresh());
    }

    public function verifyAttachmentForTicket(SupportTicket $ticket, SupportTicketAttachment $attachment): void
    {
        if (! $this->attachments->belongsToTicket($attachment, $ticket->id)) {
            abort(404);
        }
    }

    public function close(SupportTicket $ticket): void
    {
        if ($ticket->isClosed()) {
            return;
        }

        $ticket->update([
            'status' => SupportTicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }

    public function reopen(SupportTicket $ticket): void
    {
        if (! $ticket->isClosed()) {
            return;
        }

        $ticket->update([
            'status' => SupportTicketStatus::Open,
            'closed_at' => null,
        ]);
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
                    route('admin.support.attachments.download', [$ticket, $attachment]),
                )
                : null,
        ];
    }

    /**
     * @return list<array{id: int, orderNumber: string, status: string, statusTone: string, packageTitle: string}>
     */
    private function recentOrders(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return $user->orders()
            ->with('coursePackage')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'status' => ProfileStatusLabels::orderStatus($order->status),
                'statusTone' => AdminStatusLabels::orderStatusTone($order->status),
                'packageTitle' => $order->coursePackage?->title ?? '',
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, packageTitle: string, status: string, statusTone: string}>
     */
    private function recentLicenses(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return $user->spotPlayerLicenses()
            ->with('coursePackage')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($license) => [
                'id' => $license->id,
                'packageTitle' => $license->coursePackage?->title ?? '',
                'status' => ProfileStatusLabels::licenseStatus($license->status),
                'statusTone' => AdminStatusLabels::licenseStatusTone($license->status),
            ])
            ->all();
    }

    private function senderLabel(SupportTicketMessage $message): string
    {
        return match ($message->sender_type) {
            SupportTicketMessageSenderType::User => $message->user?->name ?? 'کاربر',
            SupportTicketMessageSenderType::Admin => 'پشتیبانی',
            SupportTicketMessageSenderType::System => 'سیستم',
        };
    }
}
