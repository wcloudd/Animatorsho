<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderPaymentCompletionService;
use App\Services\ZarinpalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutZarinpalCallbackController extends Controller
{
    public function __construct(
        private readonly ZarinpalService $zarinpal,
        private readonly OrderPaymentCompletionService $orderPaymentCompletion,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $authority = $request->query('Authority');
        $status = $request->query('Status');

        if (! is_string($authority) || $authority === '') {
            return redirect()->route('checkout.result');
        }

        $payment = Payment::query()
            ->where('method', PaymentMethod::Zarinpal)
            ->where('meta->authority', $authority)
            ->with('order')
            ->first();

        if ($payment === null || $payment->order === null) {
            return redirect()->route('checkout.result');
        }

        $order = $payment->order;

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
}
