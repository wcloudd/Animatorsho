<?php

namespace App\Services\Admin;

use App\Models\ConsultationRequest;
use App\Support\Admin\AdminListSearch;
use App\Support\ConsultationRequestStatusLabels;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminConsultationListService
{
    /**
     * @return array{
     *     consultations: LengthAwarePaginator<int, array{
     *         id: int,
     *         name: string,
     *         mobile: string,
     *         note: ?string,
     *         level: ?string,
     *         interest: ?string,
     *         age: ?string,
     *         status: string,
     *         statusValue: string,
     *         statusTone: string,
     *         adminNote: ?string,
     *         createdAt: ?string,
     *         updatedAt: ?string
     *     }>,
     *     filters: array{status: ?string, q: ?string},
     *     statusOptions: list<array{value: string, label: string}>
     * }
     */
    public function listForAdmin(
        ?string $statusFilter = null,
        ?string $search = null,
    ): array {
        $query = ConsultationRequest::query()->latest();

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        AdminListSearch::apply($query, $search, function (Builder $searchQuery, string $pattern, string $term): void {
            $searchQuery
                ->where('name', 'like', $pattern)
                ->orWhere('mobile', 'like', $pattern)
                ->orWhere('note', 'like', $pattern);

            $statusMatch = AdminListSearch::matchesEnumKeyword($term, [
                'جدید' => 'new',
                'تماس' => 'contacted',
                'پیگیری' => 'follow_up',
                'خرید' => 'converted',
                'کنسل' => 'cancelled',
            ]);

            if ($statusMatch !== null) {
                $searchQuery->orWhere('status', $statusMatch);
            }
        });

        $normalizedSearch = AdminListSearch::normalize($search);

        $consultations = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ConsultationRequest $request) => $this->toListItem($request));

        return [
            'consultations' => $consultations,
            'filters' => [
                'status' => $statusFilter !== '' ? $statusFilter : null,
                'q' => $normalizedSearch,
            ],
            'statusOptions' => ConsultationRequestStatusLabels::statusOptions(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     mobile: string,
     *     note: ?string,
     *     level: ?string,
     *     interest: ?string,
     *     age: ?string,
     *     status: string,
     *     statusValue: string,
     *     statusTone: string,
     *     adminNote: ?string,
     *     createdAt: ?string,
     *     updatedAt: ?string
     * }
     */
    public function toListItem(ConsultationRequest $request): array
    {
        return [
            'id' => $request->id,
            'name' => $request->name,
            'mobile' => $request->mobile,
            'note' => $request->note,
            'level' => ConsultationRequestStatusLabels::level($request->level),
            'interest' => ConsultationRequestStatusLabels::interest($request->interest),
            'age' => $request->age,
            'status' => ConsultationRequestStatusLabels::status($request->status),
            'statusValue' => $request->status->value,
            'statusTone' => ConsultationRequestStatusLabels::statusTone($request->status),
            'adminNote' => $request->admin_note,
            'createdAt' => $request->created_at?->format('Y/m/d H:i'),
            'updatedAt' => $request->updated_at?->format('Y/m/d H:i'),
        ];
    }
}
