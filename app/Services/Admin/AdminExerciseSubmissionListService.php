<?php

namespace App\Services\Admin;

use App\Models\ExerciseSubmission;
use App\Support\Admin\AdminListSearch;
use App\Support\ExerciseSubmissionStatusLabels;
use App\Support\JalaliDateFormatter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminExerciseSubmissionListService
{
    /**
     * @return array{
     *     submissions: LengthAwarePaginator<int, array{
     *         id: int,
     *         studentName: string,
     *         studentMobile: ?string,
     *         title: string,
     *         status: string,
     *         statusValue: string,
     *         statusTone: string,
     *         submittedAtLabel: string,
     *         reviewUrl: string
     *     }>,
     *     filters: array{status: ?string, q: ?string},
     *     statusOptions: list<array{value: string, label: string}>
     * }
     */
    public function listForAdmin(
        ?string $statusFilter = null,
        ?string $search = null,
    ): array {
        $query = ExerciseSubmission::query()
            ->with('user')
            ->latest();

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        AdminListSearch::apply($query, $search, function (Builder $searchQuery, string $pattern, string $term): void {
            $searchQuery
                ->where('title', 'like', $pattern)
                ->orWhere('description', 'like', $pattern)
                ->orWhereHas('user', function (Builder $userQuery) use ($pattern): void {
                    $userQuery
                        ->where('name', 'like', $pattern)
                        ->orWhere('mobile', 'like', $pattern);
                });

            $statusMatch = AdminListSearch::matchesEnumKeyword($term, [
                'ارسال' => 'submitted',
                'بررسی' => 'reviewing',
                'اصلاح' => 'needs_revision',
                'تأیید' => 'approved',
            ]);

            if ($statusMatch !== null) {
                $searchQuery->orWhere('status', $statusMatch);
            }
        });

        $normalizedSearch = AdminListSearch::normalize($search);

        $submissions = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ExerciseSubmission $submission) => $this->toListItem($submission));

        return [
            'submissions' => $submissions,
            'filters' => [
                'status' => $statusFilter !== '' ? $statusFilter : null,
                'q' => $normalizedSearch,
            ],
            'statusOptions' => ExerciseSubmissionStatusLabels::statusOptions(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     studentName: string,
     *     studentMobile: ?string,
     *     title: string,
     *     status: string,
     *     statusValue: string,
     *     statusTone: string,
     *     submittedAtLabel: string,
     *     reviewUrl: string
     * }
     */
    public function toListItem(ExerciseSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'studentName' => $submission->user->name,
            'studentMobile' => $submission->user->mobile,
            'title' => $submission->title,
            'status' => ExerciseSubmissionStatusLabels::status($submission->status),
            'statusValue' => $submission->status->value,
            'statusTone' => ExerciseSubmissionStatusLabels::statusTone($submission->status),
            'submittedAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($submission->created_at),
            'reviewUrl' => route('admin.exercise-submissions.show', $submission),
        ];
    }
}
