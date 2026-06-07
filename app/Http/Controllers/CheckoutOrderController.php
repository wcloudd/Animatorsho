<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\StoreCheckoutOrderRequest;
use App\Models\Order;
use App\Services\CheckoutOrderService;
use App\Services\PaymentReceiptStorageService;
use App\Services\Sms\SmsNotifier;
use App\Services\ZarinpalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckoutOrderController extends Controller
{
    public function __construct(
        private readonly CheckoutOrderService $checkoutOrders,
        private readonly ZarinpalService $zarinpal,
        private readonly PaymentReceiptStorageService $receipts,
        private readonly SmsNotifier $smsNotifier,
    ) {}

    public function store(StoreCheckoutOrderRequest $request): RedirectResponse|Response
    {
        $validated = $request->validated();

        $customerData = [
            'customer_name' => $validated['customer_name'],
            'customer_mobile' => $validated['customer_mobile'],
        ];

        $installmentData = $validated['payment'] === 'installment'
            ? [
                'installment_term' => $validated['installment_term'],
                'note' => $validated['note'] ?? null,
            ]
            : null;

        $paymentChannel = $validated['payment_channel'] ?? 'online';

        $result = $this->checkoutOrders->create(
            $request->user(),
            $validated['package'],
            $validated['payment'],
            $validated['chapter'] ?? null,
            $customerData,
            $installmentData,
            $paymentChannel,
        );

        if ($validated['payment'] === 'installment') {
            return redirect()->route('checkout.result', [
                'status' => $result['resultStatus'],
                'order' => $result['order']->order_number,
            ]);
        }

        if ($paymentChannel === 'card_to_card') {
            return $this->completeCardToCardCheckout(
                $result['order'],
                $request->file('receipt_image'),
            );
        }

        $order = $result['order'];
        $payment = $order->payments()->firstOrFail();

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

        DB::transaction(function () use ($payment, $gatewayResult): void {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'meta' => array_merge($payment->meta ?? [], [
                    'gateway_error' => $gatewayResult->errorMessage,
                    'failed_at' => now()->toIso8601String(),
                ]),
            ]);
        });

        return redirect()->route('checkout.result', [
            'status' => 'failed',
            'order' => $order->order_number,
        ]);
    }

    private function completeCardToCardCheckout(
        Order $order,
        ?UploadedFile $receiptImage,
    ): RedirectResponse {
        $payment = $order->payments()->firstOrFail();
        $storedPath = null;

        try {
            DB::transaction(function () use ($payment, $receiptImage, &$storedPath): void {
                if (! $receiptImage instanceof UploadedFile) {
                    throw new \InvalidArgumentException('Receipt image is required.');
                }

                $receiptMeta = $this->receipts->store($payment, $receiptImage);
                $storedPath = $receiptMeta['receipt_path'];

                $payment->update([
                    'meta' => array_merge($payment->meta ?? [], $receiptMeta),
                ]);
            });
        } catch (\Throwable $exception) {
            if (is_string($storedPath) && $storedPath !== '') {
                $this->receipts->delete($storedPath);
            }

            throw $exception;
        }

        $this->smsNotifier->notifyCardToCardSubmitted($order);
        $this->smsNotifier->notifyAdminCardToCardReview($order);

        return redirect()->route('checkout.result', [
            'status' => 'manual-review',
            'order' => $order->order_number,
        ]);
    }
}
