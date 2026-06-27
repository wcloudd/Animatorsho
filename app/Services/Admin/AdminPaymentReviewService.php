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

        // Approving a card-to-card installment down-payment receipt only settles
        // the down payment and moves the order into the same review state the
        // online down payment reaches. No license is issued at this step.
        if ($this->isInstallmentDownPaymentReceipt($payment)) {
            $this->approveInstallmentDownPayment($payment);

            return null;
        }

        return $this->orderPaymentCompletion->markOrderPaid($payment->order);
    }

    /**
     * A card-to-card installment down payment whose receipt is awaiting review.
     */
    public function isInstallmentDownPaymentReceipt(Payment $payment): bool
    {
        return $payment->method === PaymentMethod::Installment
            && $payment->order?->status === OrderStatus::InstallmentDownPaymentReview;
    }

    private function approveInstallmentDownPayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $order = $payment->order;

            $payment->update([
                // Keep the payment in review: the down payment is now settled,
                // but the installment request itself still needs final approval.
                'status' => PaymentStatus::Reviewing,
                'paid_at' => $payment->paid_at ?? now(),
                'meta' => array_merge($payment->meta ?? [], [
                    'down_payment_paid_at' => now()->toIso8601String(),
                    'down_payment_channel' => 'card_to_card',
                    'down_payment_receipt_approved_at' => now()->toIso8601String(),
                ]),
            ]);

            if ($order !== null) {
                $order->update([
                    'status' => OrderStatus::InstallmentReview,
                ]);
            }
        });

        $order = $payment->fresh()?->order;

        if ($order !== null) {
            $this->smsNotifier->notifyInstallmentRequestSubmitted($order);
        }
    }

    public function reject(Payment $payment, ?string $note = null): void
    {
        $reason = $this->guardFailureReason($payment);

        if ($reason !== null) {
            throw new InvalidArgumentException($reason);
        }

        $isInstallment = $payment->method === PaymentMethod::Installment;
        $isInstallmentDownPaymentReceipt = $this->isInstallmentDownPaymentReceipt($payment);

        DB::transaction(function () use ($payment, $note, $isInstallment, $isInstallmentDownPaymentReceipt): void {
            $order = $payment->order;

            $rejectionMeta = array_merge($payment->meta ?? [], array_filter([
                'rejection_note' => $note,
                'rejected_at' => now()->toIso8601String(),
            ]));

            if ($isInstallmentDownPaymentReceipt) {
                // No money was captured for a card-to-card down payment, so fail
                // the payment (the user can retry) and flag the order rejected.
                $payment->update([
                    'status' => PaymentStatus::Failed,
                    'meta' => $rejectionMeta,
                ]);

                if ($order !== null && $order->status !== OrderStatus::Paid) {
                    $order->update([
                        'status' => OrderStatus::InstallmentRejected,
                    ]);
                }

                return;
            }

            if ($isInstallment) {
                // The down payment was already captured via Zarinpal. Preserve the
                // money trail (paid_at, tracking_code, down_payment_*) and only mark
                // the installment request itself as rejected. Refunds are manual.
                $payment->update([
                    'status' => PaymentStatus::Paid,
                    'meta' => $rejectionMeta,
                ]);

                if ($order !== null && $order->status !== OrderStatus::Paid) {
                    $order->update([
                        'status' => OrderStatus::InstallmentRejected,
                    ]);
                }

                return;
            }

            $payment->update([
                'status' => PaymentStatus::Failed,
                'meta' => $rejectionMeta,
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

        if (! in_array($order->status, [
            OrderStatus::InstallmentReview,
            OrderStatus::InstallmentDownPaymentReview,
        ], true)) {
            return 'The related order is not awaiting installment review.';
        }

        // A card-to-card down payment must have its receipt uploaded first.
        if (
            $order->status === OrderStatus::InstallmentDownPaymentReview
            && ! $this->receipts->hasReceipt($payment)
        ) {
            return 'A receipt must be uploaded before this payment can be reviewed.';
        }

        return null;
    }
}
