<?php

namespace App\Services\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Support\ProfileStatusLabels;
use App\Support\TomanFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminFinanceSummaryService
{
    private const int TOP_PACKAGES_LIMIT = 3;

    /**
     * @return array{
     *     confirmedRevenueTotal: int,
     *     confirmedRevenueTotalFormatted: string,
     *     confirmedRevenueToday: int,
     *     confirmedRevenueTodayFormatted: string,
     *     confirmedRevenueCurrentMonth: int,
     *     confirmedRevenueCurrentMonthFormatted: string,
     *     successfulPaymentsCount: int,
     *     pendingPaymentsCount: int,
     *     failedOrCancelledCount: int,
     *     reviewingCardToCardCount: int,
     *     reviewingCardToCardAmount: int,
     *     reviewingCardToCardAmountFormatted: string,
     *     reviewingInstallmentCount: int,
     *     reviewingInstallmentAmount: int,
     *     reviewingInstallmentAmountFormatted: string,
     *     paidByMethod: list<array{
     *         method: string,
     *         label: string,
     *         count: int,
     *         amountToman: int,
     *         amountFormatted: string
     *     }>,
     *     topPackages: list<array{
     *         packageId: int,
     *         title: string,
     *         paidCount: int,
     *         revenueToman: int,
     *         revenueFormatted: string
     *     }>,
     *     externalGrantsCount: int,
     *     externalGrantsAmount: int,
     *     externalGrantsAmountFormatted: string,
     *     activeLicensesCount: int
     * }
     */
    public function forDashboard(): array
    {
        $startOfToday = now()->startOfDay();
        $startOfMonth = now()->startOfMonth();

        $confirmedRevenueTotal = $this->sumPaidAmount();
        $confirmedRevenueToday = $this->sumPaidAmount(
            $this->paidPaymentsQuery()->where('paid_at', '>=', $startOfToday),
        );
        $confirmedRevenueCurrentMonth = $this->sumPaidAmount(
            $this->paidPaymentsQuery()->where('paid_at', '>=', $startOfMonth),
        );

        $reviewingCardToCardAmount = $this->sumAmount(
            $this->reviewingPaymentsQuery(PaymentMethod::CardToCard),
        );
        $reviewingInstallmentAmount = $this->sumAmount(
            $this->reviewingPaymentsQuery(PaymentMethod::Installment),
        );
        $externalPaidQuery = $this->paidPaymentsQuery()->where('method', PaymentMethod::External);
        $externalGrantsAmount = $this->sumAmount(clone $externalPaidQuery);

        return [
            'confirmedRevenueTotal' => $confirmedRevenueTotal,
            'confirmedRevenueTotalFormatted' => TomanFormatter::format($confirmedRevenueTotal),
            'confirmedRevenueToday' => $confirmedRevenueToday,
            'confirmedRevenueTodayFormatted' => TomanFormatter::format($confirmedRevenueToday),
            'confirmedRevenueCurrentMonth' => $confirmedRevenueCurrentMonth,
            'confirmedRevenueCurrentMonthFormatted' => TomanFormatter::format($confirmedRevenueCurrentMonth),
            'successfulPaymentsCount' => $this->paidPaymentsQuery()->count(),
            'pendingPaymentsCount' => $this->pendingPaymentsQuery()->count(),
            'failedOrCancelledCount' => Payment::query()
                ->where('status', PaymentStatus::Failed)
                ->count(),
            'reviewingCardToCardCount' => $this->reviewingPaymentsQuery(PaymentMethod::CardToCard)->count(),
            'reviewingCardToCardAmount' => $reviewingCardToCardAmount,
            'reviewingCardToCardAmountFormatted' => TomanFormatter::format($reviewingCardToCardAmount),
            'reviewingInstallmentCount' => $this->reviewingPaymentsQuery(PaymentMethod::Installment)->count(),
            'reviewingInstallmentAmount' => $reviewingInstallmentAmount,
            'reviewingInstallmentAmountFormatted' => TomanFormatter::format($reviewingInstallmentAmount),
            'paidByMethod' => $this->paidByMethod(),
            'topPackages' => $this->topPackages(),
            'externalGrantsCount' => (clone $externalPaidQuery)->count(),
            'externalGrantsAmount' => $externalGrantsAmount,
            'externalGrantsAmountFormatted' => TomanFormatter::format($externalGrantsAmount),
            'activeLicensesCount' => SpotPlayerLicense::query()
                ->where('status', SpotPlayerLicenseStatus::Active)
                ->count(),
        ];
    }

    /**
     * @return Builder<Payment>
     */
    private function paidPaymentsQuery(): Builder
    {
        return Payment::query()->where('status', PaymentStatus::Paid);
    }

    /**
     * @return Builder<Payment>
     */
    private function pendingPaymentsQuery(): Builder
    {
        return Payment::query()->whereIn('status', [
            PaymentStatus::Pending,
            PaymentStatus::Reviewing,
        ]);
    }

    /**
     * @return Builder<Payment>
     */
    private function reviewingPaymentsQuery(PaymentMethod $method): Builder
    {
        return Payment::query()
            ->where('status', PaymentStatus::Reviewing)
            ->where('method', $method);
    }

    /**
     * @param  Builder<Payment>|null  $query
     */
    private function sumPaidAmount(?Builder $query = null): int
    {
        $query ??= $this->paidPaymentsQuery();

        return $this->sumAmount($query);
    }

    /**
     * @param  Builder<Payment>  $query
     */
    private function sumAmount(Builder $query): int
    {
        return (int) ($query->sum('amount_toman') ?? 0);
    }

    /**
     * @return list<array{
     *     method: string,
     *     label: string,
     *     count: int,
     *     amountToman: int,
     *     amountFormatted: string
     * }>
     */
    private function paidByMethod(): array
    {
        $rows = Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->selectRaw('method, COUNT(*) as payment_count, COALESCE(SUM(amount_toman), 0) as total_amount')
            ->groupBy('method')
            ->orderByDesc('total_amount')
            ->get();

        return $rows->map(function (Payment $row): array {
            $method = $this->resolvePaymentMethod($row->getAttribute('method'));

            return [
                'method' => $method->value,
                'label' => ProfileStatusLabels::paymentMethod($method),
                'count' => (int) $row->getAttribute('payment_count'),
                'amountToman' => (int) $row->getAttribute('total_amount'),
                'amountFormatted' => TomanFormatter::format((int) $row->getAttribute('total_amount')),
            ];
        })->values()->all();
    }

    private function resolvePaymentMethod(mixed $value): PaymentMethod
    {
        if ($value instanceof PaymentMethod) {
            return $value;
        }

        return PaymentMethod::from((string) $value);
    }

    /**
     * @return list<array{
     *     packageId: int,
     *     title: string,
     *     paidCount: int,
     *     revenueToman: int,
     *     revenueFormatted: string
     * }>
     */
    private function topPackages(): array
    {
        $rows = Payment::query()
            ->where('payments.status', PaymentStatus::Paid)
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->whereNotNull('orders.course_package_id')
            ->selectRaw('orders.course_package_id as package_id, COUNT(*) as paid_count, COALESCE(SUM(payments.amount_toman), 0) as revenue_toman')
            ->groupBy('orders.course_package_id')
            ->orderByDesc('revenue_toman')
            ->limit(self::TOP_PACKAGES_LIMIT)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        /** @var Collection<int, CoursePackage> $packages */
        $packages = CoursePackage::query()
            ->whereIn('id', $rows->pluck('package_id'))
            ->get()
            ->keyBy('id');

        return $rows->map(function (Payment $row) use ($packages): array {
            $packageId = (int) $row->getAttribute('package_id');
            $package = $packages->get($packageId);
            $revenueToman = (int) $row->getAttribute('revenue_toman');

            return [
                'packageId' => $packageId,
                'title' => $package?->title ?? '—',
                'paidCount' => (int) $row->getAttribute('paid_count'),
                'revenueToman' => $revenueToman,
                'revenueFormatted' => TomanFormatter::format($revenueToman),
            ];
        })->values()->all();
    }
}
