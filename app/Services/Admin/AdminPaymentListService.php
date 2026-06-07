<?php

namespace App\Services\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentReceiptStorageService;
use App\Support\Admin\AdminListFocus;
use App\Support\Admin\AdminListSearch;
use App\Support\AdminStatusLabels;
use App\Support\InstallmentTermLabels;
use App\Support\ProfileStatusLabels;
use App\Support\TomanFormatter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminPaymentListService
{
    public function __construct(
        private readonly AdminPaymentReviewService $paymentReview,
        private readonly PaymentReceiptStorageService $receipts,
    ) {}

    /**
     * @return array{
     *     payments: LengthAwarePaginator<int, array<string, mixed>>,
     *     filters: array{status: ?string, q: ?string, focus: ?int},
     *     statusOptions: list<array{value: string, label: string}>
     * }
     */
    public function listForAdmin(?string $statusFilter = null, ?string $search = null, ?int $focus = null): array
    {
        $focusId = AdminListFocus::normalize($focus);

        $query = Payment::query()
            ->with(['order.user', 'order.coursePackage'])
            ->latest();

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        AdminListFocus::apply($query, $focusId);

        AdminListSearch::apply($query, $search, function (Builder $searchQuery, string $pattern, string $term): void {
            $searchQuery
                ->where('tracking_code', 'like', $pattern)
                ->orWhere('method', 'like', $pattern)
                ->orWhereHas('order', function (Builder $orderQuery) use ($pattern): void {
                    $orderQuery
                        ->where('order_number', 'like', $pattern)
                        ->orWhere('customer_name', 'like', $pattern)
                        ->orWhere('customer_mobile', 'like', $pattern)
                        ->orWhereHas('user', function (Builder $userQuery) use ($pattern): void {
                            $userQuery
                                ->where('name', 'like', $pattern)
                                ->orWhere('email', 'like', $pattern)
                                ->orWhere('mobile', 'like', $pattern);
                        })
                        ->orWhereHas('coursePackage', fn (Builder $packageQuery) => $packageQuery->where('title', 'like', $pattern));
                });

            $methodMatch = AdminListSearch::matchesEnumKeyword($term, [
                'کارت' => PaymentMethod::CardToCard->value,
                'قسط' => PaymentMethod::Installment->value,
                'زرین' => PaymentMethod::Zarinpal->value,
            ]);

            if ($methodMatch !== null) {
                $searchQuery->orWhere('method', $methodMatch);
            }
        });

        $normalizedSearch = AdminListSearch::normalize($search);

        $payments = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Payment $payment): array => $this->toListItem($payment));

        return [
            'payments' => $payments,
            'filters' => [
                'status' => $statusFilter,
                'q' => $normalizedSearch,
                'focus' => $focusId,
            ],
            'statusOptions' => $this->statusOptions(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(Payment $payment): array
    {
        $status = $payment->status;
        $order = $payment->order;
        $canReview = $this->paymentReview->canReview($payment);
        $hasReceipt = $this->receipts->hasReceipt($payment);

        return [
            'id' => $payment->id,
            'orderNumber' => $order?->order_number ?? '—',
            'userName' => $order?->user?->name ?? '—',
            'userEmail' => $order?->user?->email ?? '—',
            'customerName' => $order?->customer_name,
            'customerMobile' => $order?->customer_mobile,
            'packageTitle' => $order?->coursePackage?->title ?? '—',
            'method' => ProfileStatusLabels::paymentMethod($payment->method),
            'methodValue' => $payment->method->value,
            'status' => ProfileStatusLabels::paymentStatus($status),
            'statusValue' => $status->value,
            'statusTone' => AdminStatusLabels::paymentStatusTone($status),
            'amountToman' => $payment->amount_toman,
            'amountFormatted' => TomanFormatter::format($payment->amount_toman),
            'trackingCode' => $payment->tracking_code,
            'paidAt' => $payment->paid_at?->toIso8601String(),
            'createdAt' => $payment->created_at?->toIso8601String(),
            'receiptUrl' => $hasReceipt
                ? route('admin.payments.receipt', $payment)
                : null,
            'canApprove' => $canReview,
            'canReject' => $canReview,
            'rejectionNote' => $this->rejectionNote($payment->meta),
            'installmentRequestedTerm' => InstallmentTermLabels::fromPaymentMeta($payment->meta),
            'installmentNote' => InstallmentTermLabels::noteFromPaymentMeta($payment->meta),
            'installment' => $this->installmentSnapshot($payment),
            'meta' => $this->formatMeta($payment->meta),
        ];
    }

    /**
     * @return array{
     *     cashPriceToman: ?int,
     *     installmentTotalToman: ?int,
     *     downPaymentToman: ?int,
     *     remainingToman: ?int,
     *     downPaymentPercent: ?int,
     *     months: ?int,
     *     downPaymentPaidAt: ?string,
     *     downPaymentRef: ?string,
     *     downPaymentCaptured: bool
     * }|null
     */
    private function installmentSnapshot(Payment $payment): ?array
    {
        if ($payment->method !== PaymentMethod::Installment) {
            return null;
        }

        $meta = $payment->meta ?? [];

        $downPaymentPaidAt = $meta['down_payment_paid_at'] ?? null;
        $downPaymentRef = $meta['down_payment_ref'] ?? null;

        return [
            'cashPriceToman' => $this->intOrNull($meta['cash_price_toman'] ?? null),
            'installmentTotalToman' => $this->intOrNull($meta['installment_total_toman'] ?? null),
            'downPaymentToman' => $this->intOrNull($meta['down_payment_toman'] ?? null),
            'remainingToman' => $this->intOrNull($meta['remaining_toman'] ?? null),
            'downPaymentPercent' => $this->intOrNull($meta['down_payment_percent'] ?? null),
            'months' => $this->intOrNull($meta['months'] ?? null),
            'downPaymentPaidAt' => is_string($downPaymentPaidAt) ? $downPaymentPaidAt : null,
            'downPaymentRef' => is_string($downPaymentRef) ? $downPaymentRef : (is_int($downPaymentRef) ? (string) $downPaymentRef : null),
            'downPaymentCaptured' => $downPaymentPaidAt !== null,
        ];
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && is_numeric($value)) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function rejectionNote(?array $meta): ?string
    {
        if ($meta === null) {
            return null;
        }

        $note = $meta['rejection_note'] ?? null;

        if (! is_string($note) || trim($note) === '') {
            return null;
        }

        return trim($note);
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function formatMeta(?array $meta): ?string
    {
        if ($meta === null || $meta === []) {
            return null;
        }

        $safeMeta = $meta;

        unset($safeMeta['receipt_path']);

        if ($safeMeta === []) {
            return null;
        }

        $encoded = json_encode($safeMeta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return is_string($encoded) ? $encoded : null;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => PaymentStatus::Pending->value, 'label' => ProfileStatusLabels::paymentStatus(PaymentStatus::Pending)],
            ['value' => PaymentStatus::Paid->value, 'label' => ProfileStatusLabels::paymentStatus(PaymentStatus::Paid)],
            ['value' => PaymentStatus::Failed->value, 'label' => ProfileStatusLabels::paymentStatus(PaymentStatus::Failed)],
            ['value' => PaymentStatus::Reviewing->value, 'label' => ProfileStatusLabels::paymentStatus(PaymentStatus::Reviewing)],
        ];
    }
}
