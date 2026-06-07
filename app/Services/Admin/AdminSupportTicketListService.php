<?php

namespace App\Services\Admin;

use App\Models\SupportTicket;
use App\Support\SupportTicketStatusLabels;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminSupportTicketListService
{
    /**
     * @return array{
     *     tickets: LengthAwarePaginator<int, array{
     *         id: int,
     *         subject: string,
     *         status: string,
     *         statusValue: string,
     *         statusTone: string,
     *         category: string,
     *         categoryValue: string,
     *         customerName: string,
     *         customerMobile: ?string,
     *         userName: string,
     *         userEmail: string,
     *         createdAt: ?string
     *     }>,
     *     filters: array{status: ?string, category: ?string},
     *     statusOptions: list<array{value: string, label: string}>,
     *     categoryOptions: list<array{value: string, label: string}>
     * }
     */
    public function listForAdmin(?string $statusFilter = null, ?string $categoryFilter = null): array
    {
        $query = SupportTicket::query()
            ->with('user')
            ->latest();

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($categoryFilter !== null && $categoryFilter !== '') {
            $query->where('category', $categoryFilter);
        }

        $tickets = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SupportTicket $ticket) => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => SupportTicketStatusLabels::status($ticket->status),
                'statusValue' => $ticket->status->value,
                'statusTone' => SupportTicketStatusLabels::statusTone($ticket->status),
                'category' => SupportTicketStatusLabels::category($ticket->category),
                'categoryValue' => $ticket->category->value,
                'customerName' => $ticket->customer_name,
                'customerMobile' => $ticket->customer_mobile,
                'userName' => $ticket->user?->name ?? '',
                'userEmail' => $ticket->user?->email ?? '',
                'createdAt' => $ticket->created_at?->format('Y/m/d H:i'),
            ]);

        return [
            'tickets' => $tickets,
            'filters' => [
                'status' => $statusFilter !== '' ? $statusFilter : null,
                'category' => $categoryFilter !== '' ? $categoryFilter : null,
            ],
            'statusOptions' => SupportTicketStatusLabels::statusOptions(),
            'categoryOptions' => SupportTicketStatusLabels::categoryOptions(),
        ];
    }
}
