<?php

namespace App\Services\Admin;

use App\Enums\OrderPaymentType;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Support\ExternalEnrollmentSourceLabels;
use App\Support\ProfileStatusLabels;
use App\Support\TomanFormatter;

class AdminManualEnrollmentListService
{
    /**
     * @return array{
     *     packages: list<array{id: int, title: string, slug: string, priceFormatted: string}>,
     *     sourceOptions: list<array{value: string, label: string}>,
     *     recentGrants: list<array{
     *         id: int,
     *         orderNumber: string,
     *         customerName: string|null,
     *         customerMobile: string|null,
     *         packageTitle: string,
     *         sourceLabel: string|null,
     *         licenseStatus: string|null,
     *         createdAt: string|null
     *     }>
     * }
     */
    public function formData(): array
    {
        $packages = CoursePackage::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(fn (CoursePackage $package): array => [
                'id' => $package->id,
                'title' => $package->title,
                'slug' => $package->slug,
                'priceFormatted' => TomanFormatter::format($package->price_toman),
            ])
            ->values()
            ->all();

        return [
            'packages' => $packages,
            'sourceOptions' => ExternalEnrollmentSourceLabels::options(),
            'recentGrants' => $this->recentGrants(),
        ];
    }

    /**
     * @return list<array{
     *     id: int,
     *     orderNumber: string,
     *     customerName: string|null,
     *     customerMobile: string|null,
     *     packageTitle: string,
     *     sourceLabel: string|null,
     *     licenseStatus: string|null,
     *     createdAt: string|null
     * }>
     */
    private function recentGrants(): array
    {
        return Order::query()
            ->with([
                'coursePackage',
                'payments' => fn ($query) => $query->latest()->limit(1),
                'spotPlayerLicense',
            ])
            ->where('payment_type', OrderPaymentType::External)
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (Order $order): array {
                $latestPayment = $order->payments->first();
                $license = $order->spotPlayerLicense;

                return [
                    'id' => $order->id,
                    'orderNumber' => $order->order_number,
                    'customerName' => $order->customer_name,
                    'customerMobile' => $order->customer_mobile,
                    'packageTitle' => $order->coursePackage?->title ?? '—',
                    'sourceLabel' => ExternalEnrollmentSourceLabels::labelFromMeta(
                        is_array($latestPayment?->meta) ? $latestPayment->meta : null,
                    ),
                    'licenseStatus' => $license !== null
                        ? ProfileStatusLabels::licenseStatus($license->status)
                        : null,
                    'createdAt' => $order->created_at?->toIso8601String(),
                ];
            })
            ->all();
    }
}
