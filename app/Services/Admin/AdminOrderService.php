<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Services\OrderPaymentCompletionService;
use InvalidArgumentException;

class AdminOrderService
{
    public function __construct(
        private readonly OrderPaymentCompletionService $orderPaymentCompletion,
    ) {}

    public function markAsPaid(Order $order): ?SpotPlayerLicense
    {
        if ($this->requiresPaymentReview($order)) {
            throw new InvalidArgumentException(
                'This order must be approved from the payments page after review.',
            );
        }

        return $this->orderPaymentCompletion->markOrderPaid($order);
    }

    public function cancel(Order $order): void
    {
        if ($order->status === OrderStatus::Paid) {
            throw new InvalidArgumentException('Paid orders cannot be cancelled.');
        }

        if ($order->status === OrderStatus::Cancelled) {
            return;
        }

        $order->update([
            'status' => OrderStatus::Cancelled,
        ]);
    }

    /**
     * @param  array{customer_name: string, customer_mobile: string}  $customerData
     */
    public function updateCustomer(Order $order, array $customerData): Order
    {
        $order->update([
            'customer_name' => $customerData['customer_name'],
            'customer_mobile' => $customerData['customer_mobile'],
        ]);

        return $order->fresh();
    }

    public function requiresPaymentReview(Order $order): bool
    {
        $latestPayment = $order->payments()->latest()->first();

        if (! $latestPayment instanceof Payment) {
            return false;
        }

        if ($order->status === OrderStatus::InstallmentReview) {
            return $latestPayment->method === PaymentMethod::Installment;
        }

        if ($order->status !== OrderStatus::ManualReview) {
            return false;
        }

        return $latestPayment->method === PaymentMethod::CardToCard;
    }
}
