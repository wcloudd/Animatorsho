<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Services\OrderPaymentCompletionService;
use App\Services\PaymentReceiptStorageService;
use App\Services\Sms\SmsNotifier;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminPaymentReviewService
{
    public function __construct(
        private readonly OrderPaymentCompletionService $orderPaymentCompletion,
        private readonly PaymentReceiptStorageService $receipts,
        private readonly SmsNotifier $smsNotifier,
    ) {}

    public function canReview(Payment $payment): bool
    {
        return $this->guardFailureReason($payment) === null;
    }

    public function canReviewCardToCard(Payment $payment): bool
    {
        return $this->cardToCardGuardFailureReason($payment) === null;
    }

    public function canReviewInstallment(Payment $payment): bool
    {
        return $this->installmentGuardFailureReason($payment) === null;
    }

    public function approve(Payment $payment): ?SpotPlayerLicense
    {
        $reason = $this->guardFailureReason($payment);

        if ($reason !== null) {
            throw new InvalidArgumentException($reason);
        }

        return $this->orderPaymentCompletion->markOrderPaid($payment->order);
    }

    public function reject(Payment $payment, ?string $note = null): void
    {
        $reason = $this->guardFailureReason($payment);

        if ($reason !== null) {
            throw new InvalidArgumentException($reason);
        }

        $isInstallment = $payment->method === PaymentMethod::Installment;

        DB::transaction(function () use ($payment, $note): void {
            $order = $payment->order;

            $payment->update([
                'status' => PaymentStatus::Failed,
                'meta' => array_merge($payment->meta ?? [], array_filter([
                    'rejection_note' => $note,
                    'rejected_at' => now()->toIso8601String(),
                ])),
            ]);

            if ($order !== null && $order->status !== OrderStatus::Paid) {
                $order->update([
                    'status' => OrderStatus::Failed,
                ]);
            }
        });

        $order = $payment->fresh()->order;

        if ($order === null) {
            return;
        }

        if ($isInstallment) {
            $this->smsNotifier->notifyInstallmentRejected($order, $note);

            return;
        }

        $this->smsNotifier->notifyCardToCardRejected($order, $note);
    }

    public function isCardToCardManualReviewOrder(Payment $payment): bool
    {
        $order = $payment->order;

        return $payment->method === PaymentMethod::CardToCard
            && $order !== null
            && $order->status === OrderStatus::ManualReview;
    }

    public function isInstallmentReviewOrder(Payment $payment): bool
    {
        $order = $payment->order;

        return $payment->method === PaymentMethod::Installment
            && $order !== null
            && $order->status === OrderStatus::InstallmentReview;
    }

    private function guardFailureReason(Payment $payment): ?string
    {
        return match ($payment->method) {
            PaymentMethod::CardToCard => $this->cardToCardGuardFailureReason($payment),
            PaymentMethod::Installment => $this->installmentGuardFailureReason($payment),
            default => 'This payment type cannot be reviewed through this action.',
        };
    }

    private function cardToCardGuardFailureReason(Payment $payment): ?string
    {
        if ($payment->method !== PaymentMethod::CardToCard) {
            return 'Only card-to-card payments can be reviewed through this action.';
        }

        if ($payment->status !== PaymentStatus::Reviewing) {
            return 'This payment is not awaiting review.';
        }

        $order = $payment->order;

        if ($order === null) {
            return 'Payment order was not found.';
        }

        if ($order->status !== OrderStatus::ManualReview) {
            return 'The related order is not awaiting manual review.';
        }

        if (! $this->receipts->hasReceipt($payment)) {
            return 'A receipt must be uploaded before this payment can be reviewed.';
        }

        return null;
    }

    private function installmentGuardFailureReason(Payment $payment): ?string
    {
        if ($payment->method !== PaymentMethod::Installment) {
            return 'Only installment payments can be reviewed through this action.';
        }

        if ($payment->status !== PaymentStatus::Reviewing) {
            return 'This payment is not awaiting review.';
        }

        $order = $payment->order;

        if ($order === null) {
            return 'Payment order was not found.';
        }

        if ($order->status !== OrderStatus::InstallmentReview) {
            return 'The related order is not awaiting installment review.';
        }

        return null;
    }
}
