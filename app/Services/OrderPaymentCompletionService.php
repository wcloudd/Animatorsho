<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Services\Sms\SmsNotifier;
use App\Services\SpotPlayer\SpotPlayerApiProvisioningService;
use Illuminate\Support\Facades\DB;

class OrderPaymentCompletionService
{
    public function __construct(
        private readonly SpotPlayerLicenseProvisioningService $spotPlayerLicenses,
        private readonly SpotPlayerApiProvisioningService $spotPlayerApi,
        private readonly SmsNotifier $smsNotifier,
    ) {}

    public function markOrderPaid(Order $order, ?string $trackingCode = null): ?SpotPlayerLicense
    {
        if ($order->status === OrderStatus::Cancelled) {
            return null;
        }

        if ($order->status === OrderStatus::Paid) {
            return $this->finalizeLicenseProvisioning($order);
        }

        $license = DB::transaction(function () use ($order, $trackingCode): ?SpotPlayerLicense {
            $order->update([
                'status' => OrderStatus::Paid,
            ]);

            $latestPayment = $order->payments()->latest()->first();

            if ($latestPayment instanceof Payment) {
                $paymentUpdates = [
                    'status' => PaymentStatus::Paid,
                ];

                if ($latestPayment->paid_at === null) {
                    $paymentUpdates['paid_at'] = now();
                }

                if ($trackingCode !== null && $trackingCode !== '') {
                    $paymentUpdates['tracking_code'] = $trackingCode;
                }

                $latestPayment->update($paymentUpdates);
            }

            return $this->spotPlayerLicenses->provisionForPaidOrder($order->fresh());
        });

        $license = $this->attemptApiProvisioning($license);

        $latestPayment = $order->fresh()->payments()->latest()->first();

        if ($latestPayment instanceof Payment) {
            $this->smsNotifier->notifyPaymentPaid($order->fresh(), $latestPayment);
        }

        return $license;
    }

    public function finalizeLicenseProvisioning(Order $order): ?SpotPlayerLicense
    {
        $license = $this->spotPlayerLicenses->provisionForPaidOrder($order);

        return $this->attemptApiProvisioning($license);
    }

    private function attemptApiProvisioning(?SpotPlayerLicense $license): ?SpotPlayerLicense
    {
        if (! $license instanceof SpotPlayerLicense) {
            return null;
        }

        return $this->spotPlayerApi->attemptForLicense($license);
    }
}
