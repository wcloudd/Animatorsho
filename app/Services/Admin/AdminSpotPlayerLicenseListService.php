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
     *     licenses: LengthAwarePaginator<int, array<string, mixed>>
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
     * @return array<string, mixed>
     */
    private function toListItem(SpotPlayerLicense $license): array
    {
        $status = $license->status;
        $order = $license->order;
        $latestPayment = $order?->payments->first();
        $meta = is_array($license->meta) ? $license->meta : [];
        $provisionedVia = is_string($meta['provisioned_via'] ?? null) ? $meta['provisioned_via'] : 'pending';
        $courseIds = $license->coursePackage?->spotplayer_course_ids;
        $hasCourseIds = is_array($courseIds) && $courseIds !== [];

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
            'provisionedVia' => $provisionedVia,
            'provisionedViaLabel' => $this->provisionedViaLabel($provisionedVia),
            'apiFailureSummary' => is_string($meta['spotplayer_error_message'] ?? null)
                ? $meta['spotplayer_error_message']
                : (is_string($meta['last_api_error'] ?? null) ? $meta['last_api_error'] : null),
            'apiTechnicalDetails' => [
                'lastApiAttemptAt' => is_string($meta['last_api_attempt_at'] ?? null) ? $meta['last_api_attempt_at'] : null,
                'lastApiError' => is_string($meta['last_api_error'] ?? null) ? $meta['last_api_error'] : null,
                'lastApiHttpStatus' => is_int($meta['last_api_http_status'] ?? null) ? $meta['last_api_http_status'] : null,
                'spotplayerLicenseId' => is_string($meta['spotplayer_license_id'] ?? null) ? $meta['spotplayer_license_id'] : null,
                'spotplayerErrorMessage' => is_string($meta['spotplayer_error_message'] ?? null) ? $meta['spotplayer_error_message'] : null,
                'spotplayerResponseKeys' => is_array($meta['spotplayer_response_keys'] ?? null)
                    ? array_values(array_filter($meta['spotplayer_response_keys'], is_string(...)))
                    : [],
                'spotplayerResponsePreview' => is_string($meta['spotplayer_response_preview'] ?? null)
                    ? $meta['spotplayer_response_preview']
                    : null,
            ],
            'canRetryProvision' => $status === SpotPlayerLicenseStatus::Pending
                && config('spotplayer.enabled')
                && $hasCourseIds,
        ];
    }

    private function provisionedViaLabel(string $provisionedVia): string
    {
        return match ($provisionedVia) {
            'api' => 'API',
            'manual' => 'دستی',
            default => 'در انتظار',
        };
    }
}
