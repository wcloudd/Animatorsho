<?php

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentReceiptStorageService;
use App\Support\InstallmentTermLabels;
use App\Support\ProfileStatusLabels;
use App\Support\TomanFormatter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminPaymentListService
{
    public function __construct(
        private readonly AdminPaymentReviewService $paymentReview,
        private readonly PaymentReceiptStorageService $receipts,
    ) {}

    /**
     * @return array{
     *     payments: LengthAwarePaginator<int, array<string, mixed>>,
     *     filters: array{status: ?string},
     *     statusOptions: list<array{value: string, label: string}>
     * }
     */
    public function listForAdmin(?string $statusFilter = null): array
    {
        $query = Payment::query()
            ->with(['order.user', 'order.coursePackage'])
            ->latest();

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $payments = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Payment $payment): array => $this->toListItem($payment));

        return [
            'payments' => $payments,
            'filters' => [
                'status' => $statusFilter,
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
            'statusTone' => ProfileStatusLabels::paymentStatusTone($status),
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
            'meta' => $this->formatMeta($payment->meta),
        ];
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
