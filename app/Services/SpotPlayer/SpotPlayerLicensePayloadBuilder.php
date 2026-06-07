<?php

namespace App\Services\SpotPlayer;

use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\SpotPlayerLicense;

class SpotPlayerLicensePayloadBuilder
{
    /**
     * @return array<string, mixed>|null
     */
    public function build(SpotPlayerLicense $license): ?array
    {
        $license->loadMissing(['order', 'coursePackage', 'user']);

        $order = $license->order;
        $package = $license->coursePackage;

        if (! $package instanceof CoursePackage) {
            return null;
        }

        $courseIds = $package->spotplayer_course_ids;

        if (! is_array($courseIds) || $courseIds === []) {
            return null;
        }

        $name = $this->resolveCustomerName($order, $license);
        $watermarkText = $this->resolveWatermarkText($order, $name);

        $payload = [
            'course' => array_values($courseIds),
            'name' => $name,
            'watermark' => [
                'texts' => [
                    ['text' => $watermarkText],
                ],
            ],
            'device' => config('spotplayer.default_device'),
            'offline' => 0,
        ];

        if (config('spotplayer.test_mode')) {
            $payload['test'] = true;
        }

        if ($order instanceof Order && $order->order_number !== null && $order->order_number !== '') {
            $payload['payload'] = 'order:'.$order->order_number;
        } elseif ($license->order_id !== null) {
            $payload['payload'] = 'order_id:'.$license->order_id;
        }

        $limit = $package->spotplayer_access_limit;

        if (is_string($limit) && trim($limit) !== '') {
            $payload['data'] = [
                'limit' => $this->buildLimitMap($courseIds, trim($limit)),
            ];
        }

        return $payload;
    }

    private function resolveCustomerName(?Order $order, SpotPlayerLicense $license): string
    {
        if ($order !== null && is_string($order->customer_name) && trim($order->customer_name) !== '') {
            return trim($order->customer_name);
        }

        $userName = $license->user?->name;

        if (is_string($userName) && trim($userName) !== '') {
            return trim($userName);
        }

        return 'Animatorsho Customer';
    }

    private function resolveWatermarkText(?Order $order, string $fallbackName): string
    {
        if ($order !== null && is_string($order->customer_mobile) && trim($order->customer_mobile) !== '') {
            return trim($order->customer_mobile);
        }

        return $fallbackName;
    }

    /**
     * @param  list<string>  $courseIds
     * @return array<string, string>|string
     */
    private function buildLimitMap(array $courseIds, string $limit): array|string
    {
        if (count($courseIds) === 1) {
            return $limit;
        }

        $limitMap = [];

        foreach ($courseIds as $courseId) {
            $limitMap[$courseId] = $limit;
        }

        return $limitMap;
    }
}
