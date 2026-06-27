<?php

namespace App\Services\Admin;

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Admin\AdminListFocus;
use App\Support\Admin\AdminListSearch;
use App\Services\PaymentReceiptStorageService;
use App\Support\AdminStatusLabels;
use App\Support\InstallmentTermLabels;
use App\Support\ProfileStatusLabels;
use App\Support\TomanFormatter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminInstallmentListService
{
    public function __construct(
        private readonly AdminPaymentReviewService $paymentReview,
        private readonly PaymentReceiptStorageService $receipts,
    ) {}

    /**
     * @return array{
     *     installments: LengthAwarePaginator<int, array<string, mixed>>,
     *     filters: array{status: ?string, q: ?string, focus: ?int},
     *     statusOptions: list<array{value: string, label: string}>
     * }
     */
    public function listForAdmin(?string $statusFilter = null, ?string $search = null, ?int $focus = null): array
    {
        $focusId = AdminListFocus::normalize($focus);

        $query = Order::query()
            ->with([
                'user',
                'coursePackage',
                'payments' => fn ($paymentsQuery) => $paymentsQuery->latest(),
            ])
            ->where('payment_type', OrderPaymentType::Installment)
            ->latest();

        $this->applyStatusFilter($query, $statusFilter);

        AdminListFocus::apply($query, $focusId);

        AdminListSearch::apply($query, $search, function (Builder $searchQuery, string $pattern): void {
            $searchQuery
                ->where('order_number', 'like', $pattern)
                ->orWhere('customer_name', 'like', $pattern)
                ->orWhere('customer_mobile', 'like', $pattern)
                ->orWhereHas('user', function (Builder $userQuery) use ($pattern): void {
                    $userQuery
                        ->where('name', 'like', $pattern)
                        ->orWhere('email', 'like', $pattern)
                        ->orWhere('mobile', 'like', $pattern);
                })
                ->orWhereHas('coursePackage', fn (Builder $packageQuery) => $packageQuery->where('title', 'like', $pattern))
                ->orWhereHas('payments', function (Builder $paymentQuery) use ($pattern): void {
                    $paymentQuery
                        ->where('tracking_code', 'like', $pattern)
                        ->orWhere('method', PaymentMethod::Installment->value);
                });
        });

        $normalizedSearch = AdminListSearch::normalize($search);

        $installments = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order): array => $this->toListItem($order));

        return [
            'installments' => $installments,
            'filters' => [
                'status' => $statusFilter,
                'q' => $normalizedSearch,
                'focus' => $focusId,
            ],
            'statusOptions' => $this->statusOptions(),
        ];
    }

    /**
     * @param  Builder<Order>  $query
     */
    private function applyStatusFilter(Builder $query, ?string $statusFilter): void
    {
        if ($statusFilter === null || $statusFilter === '') {
            return;
        }

        match ($statusFilter) {
            'awaiting_down_payment' => $query->where('status', OrderStatus::InstallmentDownPaymentPending),
            // "Awaiting review" covers both the online down payment (InstallmentReview)
            // and the card-to-card down-payment receipt awaiting approval.
            'awaiting_review' => $query->whereIn('status', [
                OrderStatus::InstallmentReview,
                OrderStatus::InstallmentDownPaymentReview,
            ]),
            'approved' => $query->where('status', OrderStatus::Paid),
            'rejected' => $query->where('status', OrderStatus::InstallmentRejected),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(Order $order): array
    {
        $payment = $this->installmentPayment($order);
        $status = $order->status;
        $installment = $payment instanceof Payment ? $this->installmentSnapshot($payment) : null;

        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'paymentId' => $payment?->id,
            'userName' => $order->user?->name ?? '—',
            'userEmail' => $order->user?->email ?? '—',
            'customerName' => $order->customer_name,
            'customerMobile' => $order->customer_mobile,
            'packageTitle' => $order->coursePackage?->title ?? '—',
            'orderStatus' => ProfileStatusLabels::orderStatus($status),
            'orderStatusValue' => $status->value,
            'orderStatusTone' => AdminStatusLabels::orderStatusTone($status),
            'paymentStatus' => $payment instanceof Payment
                ? ProfileStatusLabels::paymentStatus($payment->status)
                : null,
            'paymentStatusValue' => $payment?->status->value,
            'paymentStatusTone' => $payment instanceof Payment
                ? AdminStatusLabels::paymentStatusTone($payment->status)
                : null,
            'installmentRequestedTerm' => $payment instanceof Payment
                ? InstallmentTermLabels::fromPaymentMeta($payment->meta)
                : null,
            'installmentNote' => $payment instanceof Payment
                ? InstallmentTermLabels::noteFromPaymentMeta($payment->meta)
                : null,
            'rejectionNote' => $payment instanceof Payment
                ? $this->rejectionNote($payment->meta)
                : null,
            'trackingCode' => $payment?->tracking_code,
            'amountToman' => $payment?->amount_toman ?? $order->final_amount_toman,
            'amountFormatted' => TomanFormatter::format($payment?->amount_toman ?? $order->final_amount_toman),
            'installment' => $installment,
            'canApprove' => $payment instanceof Payment && $this->paymentReview->canReview($payment),
            'canReject' => $payment instanceof Payment && $this->paymentReview->canReview($payment),
            'isInstallmentDownPaymentReceipt' => $payment instanceof Payment
                && $this->paymentReview->isInstallmentDownPaymentReceipt($payment),
            'receiptUrl' => $payment instanceof Payment && $this->receipts->hasReceipt($payment)
                ? route('admin.payments.receipt', $payment)
                : null,
            'createdAt' => $order->created_at?->toIso8601String(),
            'paymentReviewHref' => $payment instanceof Payment
                ? route('admin.payments.index', ['focus' => $payment->id])
                : null,
            'orderHref' => route('admin.orders.index', ['q' => $order->order_number]),
        ];
    }

    private function installmentPayment(Order $order): ?Payment
    {
        $installmentPayment = $order->payments->first(
            fn (Payment $payment): bool => $payment->method === PaymentMethod::Installment,
        );

        return $installmentPayment ?? $order->payments->first();
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
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => 'awaiting_down_payment', 'label' => 'در انتظار پیش‌پرداخت'],
            ['value' => 'awaiting_review', 'label' => 'در انتظار بررسی'],
            ['value' => 'approved', 'label' => 'تأیید شده'],
            ['value' => 'rejected', 'label' => 'رد شده'],
        ];
    }
}
