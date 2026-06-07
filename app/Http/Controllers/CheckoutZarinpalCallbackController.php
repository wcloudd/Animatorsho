<?php

namespace App\Http\Controllers;

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderPaymentCompletionService;
use App\Services\Sms\SmsNotifier;
use App\Services\Zarinpal\ZarinpalVerifyResult;
use App\Services\ZarinpalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutZarinpalCallbackController extends Controller
{
    public function __construct(
        private readonly ZarinpalService $zarinpal,
        private readonly OrderPaymentCompletionService $orderPaymentCompletion,
        private readonly SmsNotifier $smsNotifier,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $authority = $request->query('Authority');
        $status = $request->query('Status');

        if (! is_string($authority) || $authority === '') {
            return redirect()->route('checkout.result');
        }

        $payment = Payment::query()
            ->whereIn('method', [PaymentMethod::Zarinpal, PaymentMethod::Installment])
            ->where('meta->authority', $authority)
            ->with('order')
            ->first();

        if ($payment === null || $payment->order === null) {
            return redirect()->route('checkout.result');
        }

        $order = $payment->order;

        if ($order->payment_type === OrderPaymentType::Installment) {
            return $this->handleInstallmentDownPayment($payment, $order, $authority, $status);
        }

        if ($payment->status === PaymentStatus::Paid) {
            $this->orderPaymentCompletion->finalizeLicenseProvisioning($order);

            return redirect()->route('checkout.result', [
                'status' => 'success',
                'order' => $order->order_number,
            ]);
        }

        if ($status !== 'OK') {
            $this->markCallbackFailed($payment, $order);

            return redirect()->route('checkout.result', [
                'status' => 'failed',
                'order' => $order->order_number,
            ]);
        }

        $verifyResult = $this->zarinpal->verify($payment, $authority);

        if ($verifyResult->successful) {
            $order->refresh();

            if ($order->status === OrderStatus::Cancelled) {
                $this->recordVerifiedAfterCancelledOrder($payment, $verifyResult);

                return redirect()->route('checkout.result', [
                    'status' => 'payment-received-needs-support',
                    'order' => $order->order_number,
                ]);
            }

            $this->orderPaymentCompletion->markOrderPaid($order, $verifyResult->refId);

            return redirect()->route('checkout.result', [
                'status' => 'success',
                'order' => $order->order_number,
            ]);
        }

        $this->markCallbackFailed($payment, $order);

        return redirect()->route('checkout.result', [
            'status' => 'failed',
            'order' => $order->order_number,
        ]);
    }

    /**
     * Capture the 40% installment down payment. On success the order moves into
     * admin review (NOT paid/granted). The down payment money is recorded on the
     * payment so a later admin rejection can preserve the audit trail.
     */
    private function handleInstallmentDownPayment(
        Payment $payment,
        Order $order,
        string $authority,
        ?string $status,
    ): RedirectResponse {
        $alreadyCaptured = $payment->status !== PaymentStatus::Pending
            || isset($payment->meta['down_payment_paid_at']);

        if ($alreadyCaptured) {
            return redirect()->route('checkout.result', [
                'status' => 'installment-review',
                'order' => $order->order_number,
            ]);
        }

        if ($status !== 'OK') {
            $this->markCallbackFailed($payment, $order);

            return redirect()->route('checkout.result', [
                'status' => 'failed',
                'order' => $order->order_number,
            ]);
        }

        $verifyResult = $this->zarinpal->verify($payment, $authority);

        if (! $verifyResult->successful) {
            $this->markCallbackFailed($payment, $order);

            return redirect()->route('checkout.result', [
                'status' => 'failed',
                'order' => $order->order_number,
            ]);
        }

        $order->refresh();

        if ($order->status === OrderStatus::Cancelled) {
            $this->recordVerifiedAfterCancelledOrder($payment, $verifyResult);

            return redirect()->route('checkout.result', [
                'status' => 'payment-received-needs-support',
                'order' => $order->order_number,
            ]);
        }

        DB::transaction(function () use ($payment, $order, $verifyResult): void {
            $payment->update([
                'status' => PaymentStatus::Reviewing,
                'paid_at' => now(),
                'tracking_code' => $verifyResult->refId,
                'meta' => array_merge($payment->meta ?? [], [
                    'down_payment_paid_at' => now()->toIso8601String(),
                    'down_payment_ref' => $verifyResult->refId,
                ]),
            ]);

            $order->update([
                'status' => OrderStatus::InstallmentReview,
            ]);
        });

        $this->smsNotifier->notifyInstallmentRequestSubmitted($order->fresh());

        return redirect()->route('checkout.result', [
            'status' => 'installment-review',
            'order' => $order->order_number,
        ]);
    }

    private function markCallbackFailed(Payment $payment, Order $order): void
    {
        DB::transaction(function () use ($payment, $order): void {
            $payment->update([
                'status' => PaymentStatus::Failed,
            ]);

            $order->update([
                'status' => OrderStatus::Failed,
            ]);
        });
    }

    private function recordVerifiedAfterCancelledOrder(Payment $payment, ZarinpalVerifyResult $verifyResult): void
    {
        $payment->update([
            'meta' => array_merge($payment->meta ?? [], [
                'callback_anomaly' => 'cancelled_order_verified',
                'verified_after_cancel_at' => now()->toIso8601String(),
                'gateway_ref_id' => $verifyResult->refId,
            ]),
        ]);

        Log::warning('Zarinpal verified for cancelled order.', [
            'order_id' => $payment->order_id,
            'payment_id' => $payment->id,
            'order_number' => $payment->order?->order_number,
        ]);
    }
}
