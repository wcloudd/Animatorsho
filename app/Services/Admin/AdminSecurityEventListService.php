<?php

namespace App\Services\Admin;

use App\Models\SecurityEvent;
use App\Support\Admin\AdminListSearch;
use App\Support\Security\SecurityEventLabels;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

class AdminSecurityEventListService
{
    /**
     * @return array{
     *     events: LengthAwarePaginator<int, array<string, mixed>>,
     *     filters: array{
     *         event: ?string,
     *         from: ?string,
     *         to: ?string,
     *         user_id: ?int,
     *         q: ?string
     *     },
     *     eventOptions: list<array{value: string, label: string}>
     * }
     */
    public function listForAdmin(
        ?string $eventFilter = null,
        ?string $from = null,
        ?string $to = null,
        ?int $userId = null,
        ?string $search = null,
    ): array {
        $query = SecurityEvent::query()->orderByDesc('occurred_at');

        if ($eventFilter !== null && $eventFilter !== '') {
            $query->where('event', $eventFilter);
        }

        if ($userId !== null && $userId > 0) {
            $query->where('user_id', $userId);
        }

        $fromDate = $this->parseDateFilter($from);
        $toDate = $this->parseDateFilter($to);

        if ($fromDate !== null) {
            $query->whereDate('occurred_at', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('occurred_at', '<=', $toDate);
        }

        AdminListSearch::apply($query, $search, function (Builder $searchQuery, string $pattern, string $term): void {
            if (ctype_digit($term)) {
                $searchQuery->where('user_id', (int) $term);
            }

            $searchQuery
                ->orWhere('ip', 'like', $pattern)
                ->orWhere('route', 'like', $pattern);
        });

        $normalizedSearch = AdminListSearch::normalize($search);

        $events = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SecurityEvent $event): array => $this->toListItem($event));

        return [
            'events' => $events,
            'filters' => [
                'event' => $eventFilter !== '' ? $eventFilter : null,
                'from' => $fromDate,
                'to' => $toDate,
                'user_id' => $userId !== null && $userId > 0 ? $userId : null,
                'q' => $normalizedSearch,
            ],
            'eventOptions' => SecurityEventLabels::filterOptions(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(SecurityEvent $event): array
    {
        $labels = SecurityEventLabels::forEvent($event->event);

        return [
            'id' => $event->id,
            'event' => $labels['label'],
            'eventValue' => $event->event,
            'eventTone' => $labels['tone'],
            'occurredAt' => $event->occurred_at?->toIso8601String(),
            'userId' => $event->user_id,
            'userLabel' => $event->user_id !== null ? (string) $event->user_id : 'مهمان',
            'route' => $event->route,
            'method' => $event->method,
            'ip' => $event->ip,
            'userAgent' => $event->user_agent,
            'metaItems' => SecurityEventLabels::mapMetaForDisplay($event->event, $event->meta),
        ];
    }

    private function parseDateFilter(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Date::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
