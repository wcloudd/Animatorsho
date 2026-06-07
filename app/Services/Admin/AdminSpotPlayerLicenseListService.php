<?php

namespace App\Services\Admin;

use App\Enums\SpotPlayerLicenseStatus;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Support\ProfileStatusLabels;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminSpotPlayerLicenseListService
{
    /**
     * @return array{
     *     licenses: LengthAwarePaginator<int, array{
     *         id: int,
     *         userName: string,
     *         userEmail: string,
     *         packageTitle: string,
     *         orderNumber: ?string,
     *         status: string,
     *         statusValue: string,
     *         statusTone: string,
     *         licenseKey: ?string,
     *         activatedAt: ?string,
     *         canActivate: bool,
     *         canRevoke: bool
     *     }>
     * }
     */
    public function listForAdmin(): array
    {
        $licenses = SpotPlayerLicense::query()
            ->with([
                'user',
                'coursePackage',
                'order' => fn ($query) => $query->with([
                    'payments' => fn ($paymentQuery) => $paymentQuery->latest()->limit(1),
                ]),
            ])
            ->latest()
            ->paginate(20)
            ->through(fn (SpotPlayerLicense $license): array => $this->toListItem($license));

        return [
            'licenses' => $licenses,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     userName: string,
     *     userEmail: string,
     *     packageTitle: string,
     *     orderNumber: ?string,
     *     status: string,
     *     statusValue: string,
     *     statusTone: string,
     *     licenseKey: ?string,
     *     activatedAt: ?string,
     *     canActivate: bool,
     *     canRevoke: bool
     * }
     */
    private function toListItem(SpotPlayerLicense $license): array
    {
        $status = $license->status;
        $order = $license->order;
        $latestPayment = $order?->payments->first();

        return [
            'id' => $license->id,
            'userName' => $license->user?->name ?? '—',
            'userEmail' => $license->user?->email ?? '—',
            'orderCustomerName' => $order?->customer_name,
            'orderCustomerMobile' => $order?->customer_mobile,
            'packageTitle' => $license->coursePackage?->title ?? '—',
            'orderNumber' => $order?->order_number,
            'orderStatus' => $order !== null
                ? ProfileStatusLabels::orderStatus($order->status)
                : null,
            'orderStatusTone' => $order !== null
                ? ProfileStatusLabels::orderStatusTone($order->status)
                : null,
            'latestPaymentStatus' => $latestPayment instanceof Payment
                ? ProfileStatusLabels::paymentStatus($latestPayment->status)
                : null,
            'latestPaymentStatusTone' => $latestPayment instanceof Payment
                ? ProfileStatusLabels::paymentStatusTone($latestPayment->status)
                : null,
            'status' => ProfileStatusLabels::licenseStatus($status),
            'statusValue' => $status->value,
            'statusTone' => ProfileStatusLabels::licenseStatusTone($status),
            'licenseKey' => $license->license_key,
            'activatedAt' => $license->activated_at?->toIso8601String(),
            'canActivate' => in_array($status, [
                SpotPlayerLicenseStatus::Pending,
                SpotPlayerLicenseStatus::Failed,
                SpotPlayerLicenseStatus::Revoked,
            ], true),
            'canRevoke' => $status === SpotPlayerLicenseStatus::Active,
        ];
    }
}
