<?php

namespace App\Services\Admin;

use App\Models\SupportTicket;
use App\Support\Admin\AdminListSearch;
use App\Support\SupportTicketStatusLabels;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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
     *     filters: array{status: ?string, category: ?string, q: ?string},
     *     statusOptions: list<array{value: string, label: string}>,
     *     categoryOptions: list<array{value: string, label: string}>
     * }
     */
    public function listForAdmin(
        ?string $statusFilter = null,
        ?string $categoryFilter = null,
        ?string $search = null,
    ): array {
        $query = SupportTicket::query()
            ->with('user')
            ->latest();

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($categoryFilter !== null && $categoryFilter !== '') {
            $query->where('category', $categoryFilter);
        }

        AdminListSearch::apply($query, $search, function (Builder $searchQuery, string $pattern, string $term): void {
            $searchQuery
                ->where('subject', 'like', $pattern)
                ->orWhere('customer_name', 'like', $pattern)
                ->orWhere('customer_mobile', 'like', $pattern)
                ->orWhere('category', 'like', $pattern)
                ->orWhereHas('user', function (Builder $userQuery) use ($pattern): void {
                    $userQuery
                        ->where('name', 'like', $pattern)
                        ->orWhere('email', 'like', $pattern)
                        ->orWhere('mobile', 'like', $pattern);
                })
                ->orWhereHas('messages', fn (Builder $messageQuery) => $messageQuery->where('body', 'like', $pattern));

            $categoryMatch = AdminListSearch::matchesEnumKeyword($term, [
                'پرداخت' => 'payment',
                'لایسنس' => 'license',
                'دسترسی' => 'course_access',
                'مشاوره' => 'consultation',
                'فنی' => 'technical',
            ]);

            if ($categoryMatch !== null) {
                $searchQuery->orWhere('category', $categoryMatch);
            }
        });

        $normalizedSearch = AdminListSearch::normalize($search);

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
                'q' => $normalizedSearch,
            ],
            'statusOptions' => SupportTicketStatusLabels::statusOptions(),
            'categoryOptions' => SupportTicketStatusLabels::categoryOptions(),
        ];
    }
}
