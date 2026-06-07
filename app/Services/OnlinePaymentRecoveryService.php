<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class OnlinePaymentRecoveryService
{
    public function __construct(
        private readonly ZarinpalService $zarinpal,
    ) {}

    public function isRecoverableOnlineOrder(Order $order): bool
    {
        if (in_array($order->status, [
            OrderStatus::Paid,
            OrderStatus::Cancelled,
            OrderStatus::ManualReview,
            OrderStatus::InstallmentReview,
        ], true)) {
            return false;
        }

        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Failed], true)) {
            return false;
        }

        $payment = $this->latestZarinpalPayment($order);

        if ($payment === null) {
            return false;
        }

        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Failed], true)) {
            return false;
        }

        $license = $order->relationLoaded('spotPlayerLicense')
            ? $order->spotPlayerLicense
            : $order->spotPlayerLicense()->first();

        if ($license === null) {
            return true;
        }

        return in_array($license->status, [
            SpotPlayerLicenseStatus::Failed,
            SpotPlayerLicenseStatus::Revoked,
        ], true);
    }

    public function retryOnlinePayment(Order $order, User $user): RedirectResponse|Response
    {
        $this->assertRecoverableForUser($order, $user);

        $payment = $this->latestZarinpalPayment($order);

        if ($payment === null) {
            throw new InvalidArgumentException('Zarinpal payment not found for this order.');
        }

        DB::transaction(function () use ($order, $payment): void {
            if ($order->status === OrderStatus::Failed) {
                $order->update(['status' => OrderStatus::Pending]);
            }

            $retryCount = (int) (($payment->meta['retry_count'] ?? 0) + 1);

            $payment->update([
                'status' => PaymentStatus::Pending,
                'meta' => array_merge($payment->meta ?? [], [
                    'retry_requested_at' => now()->toIso8601String(),
                    'retry_count' => $retryCount,
                ]),
            ]);
        });

        $payment->refresh();
        $order->refresh();

        $gatewayResult = $this->zarinpal->request(
            $payment,
            route('checkout.zarinpal.callback'),
        );

        if ($gatewayResult->successful) {
            $payment->update([
                'meta' => array_merge($payment->meta ?? [], [
                    'authority' => $gatewayResult->authority,
                    'requested_at' => now()->toIso8601String(),
                    'sandbox' => (bool) config('zarinpal.sandbox'),
                ]),
            ]);

            return Inertia::location($gatewayResult->paymentUrl);
        }

        DB::transaction(function () use ($payment, $order, $gatewayResult): void {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'meta' => array_merge($payment->meta ?? [], [
                    'gateway_error' => $gatewayResult->errorMessage,
                    'failed_at' => now()->toIso8601String(),
                ]),
            ]);

            $order->update([
                'status' => OrderStatus::Failed,
            ]);
        });

        return redirect()
            ->route('profile')
            ->with('error', 'در حال حاضر اتصال به درگاه برقرار نشد. دوباره تلاش کنید یا سفارش را لغو کنید.');
    }

    public function cancelPendingOnlineOrder(Order $order, User $user): void
    {
        $this->assertRecoverableForUser($order, $user);

        $payment = $this->latestZarinpalPayment($order);

        if ($payment === null) {
            throw new InvalidArgumentException('Zarinpal payment not found for this order.');
        }

        DB::transaction(function () use ($order, $payment, $user): void {
            $order->update([
                'status' => OrderStatus::Cancelled,
            ]);

            $payment->update([
                'status' => PaymentStatus::Failed,
                'meta' => array_merge($payment->meta ?? [], [
                    'cancelled_by_user_at' => now()->toIso8601String(),
                    'cancelled_by' => $user->id,
                ]),
            ]);
        });
    }

    private function assertRecoverableForUser(Order $order, User $user): void
    {
        if ($order->user_id !== $user->id) {
            throw new InvalidArgumentException('You are not allowed to manage this order.');
        }

        if (! $this->isRecoverableOnlineOrder($order)) {
            throw new InvalidArgumentException('This order cannot be recovered online.');
        }
    }

    private function latestZarinpalPayment(Order $order): ?Payment
    {
        if ($order->relationLoaded('payments')) {
            return $order->payments
                ->where('method', PaymentMethod::Zarinpal)
                ->sortByDesc('id')
                ->first();
        }

        return $order->payments()
            ->where('method', PaymentMethod::Zarinpal)
            ->latest()
            ->first();
    }
}
