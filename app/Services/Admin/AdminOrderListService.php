<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Support\Admin\AdminListSearch;
use App\Support\AdminStatusLabels;
use App\Support\ExternalEnrollmentSourceLabels;
use App\Support\ProfileStatusLabels;
use App\Support\TomanFormatter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminOrderListService
{
    public function __construct(
        private readonly AdminOrderService $orders,
    ) {}

    /**
     * @return array{
     *     orders: LengthAwarePaginator<int, array{
     *         id: int,
     *         orderNumber: string,
     *         userName: string,
     *         userEmail: string,
     *         packageTitle: string,
     *         status: string,
     *         statusValue: string,
     *         statusTone: string,
     *         paymentType: string,
     *         amountToman: int,
     *         amountFormatted: string,
     *         finalAmountToman: int,
     *         finalAmountFormatted: string,
     *         createdAt: ?string,
     *         canMarkPaid: bool,
     *         canCancel: bool
     *     }>,
     *     filters: array{status: ?string, q: ?string},
     *     statusOptions: list<array{value: string, label: string}>
     * }
     */
    public function listForAdmin(?string $statusFilter = null, ?string $search = null): array
    {
        $query = Order::query()
            ->with([
                'user',
                'coursePackage',
                'payments' => fn ($query) => $query->latest()->limit(1),
                'spotPlayerLicense',
            ])
            ->latest();

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        AdminListSearch::apply($query, $search, function (Builder $searchQuery, string $pattern): void {
            $searchQuery
                ->where('order_number', 'like', $pattern)
                ->orWhere('customer_name', 'like', $pattern)
                ->orWhere('customer_mobile', 'like', $pattern)
                ->orWhereHas('user', function (Builder $userQuery) use ($pattern): void {
                    $userQuery
                        ->where('name', 'like', $pattern)
                        ->orWhere('email', 'like', $pattern)
                        ->orWhere('mobile', 'like', $pattern);
                })
                ->orWhereHas('coursePackage', fn (Builder $packageQuery) => $packageQuery->where('title', 'like', $pattern));
        });

        $normalizedSearch = AdminListSearch::normalize($search);

        $orders = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order): array => $this->toListItem($order));

        return [
            'orders' => $orders,
            'filters' => [
                'status' => $statusFilter,
                'q' => $normalizedSearch,
            ],
            'statusOptions' => $this->statusOptions(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     orderNumber: string,
     *     userName: string,
     *     userEmail: string,
     *     packageTitle: string,
     *     status: string,
     *     statusValue: string,
     *     statusTone: string,
     *     paymentType: string,
     *     amountToman: int,
     *     amountFormatted: string,
     *     finalAmountToman: int,
     *     finalAmountFormatted: string,
     *     createdAt: ?string,
     *     canMarkPaid: bool,
     *     canCancel: bool
     * }
     */
    private function toListItem(Order $order): array
    {
        $status = $order->status;
        $latestPayment = $order->payments->first();
        $license = $order->spotPlayerLicense;

        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'userName' => $order->user?->name ?? '—',
            'userEmail' => $order->user?->email ?? '—',
            'customerName' => $order->customer_name,
            'customerMobile' => $order->customer_mobile,
            'packageTitle' => $order->coursePackage?->title ?? '—',
            'status' => ProfileStatusLabels::orderStatus($status),
            'statusValue' => $status->value,
            'statusTone' => AdminStatusLabels::orderStatusTone($status),
            'paymentType' => ProfileStatusLabels::paymentType($order->payment_type),
            'amountToman' => $order->amount_toman,
            'amountFormatted' => TomanFormatter::format($order->amount_toman),
            'finalAmountToman' => $order->final_amount_toman,
            'finalAmountFormatted' => TomanFormatter::format($order->final_amount_toman),
            'latestPaymentStatus' => $latestPayment instanceof Payment
                ? ProfileStatusLabels::paymentStatus($latestPayment->status)
                : null,
            'latestPaymentStatusTone' => $latestPayment instanceof Payment
                ? AdminStatusLabels::paymentStatusTone($latestPayment->status)
                : null,
            'latestPaymentMethod' => $latestPayment instanceof Payment
                ? ProfileStatusLabels::paymentMethod($latestPayment->method)
                : null,
            'externalSourceLabel' => $latestPayment instanceof Payment
                ? ExternalEnrollmentSourceLabels::labelFromMeta(
                    is_array($latestPayment->meta) ? $latestPayment->meta : null,
                )
                : null,
            'licenseStatus' => $license instanceof SpotPlayerLicense
                ? ProfileStatusLabels::licenseStatus($license->status)
                : null,
            'licenseStatusTone' => $license instanceof SpotPlayerLicense
                ? AdminStatusLabels::licenseStatusTone($license->status)
                : null,
            'createdAt' => $order->created_at?->toIso8601String(),
            'canMarkPaid' => ! in_array($status, [OrderStatus::Paid, OrderStatus::Cancelled], true)
                && ! $this->orders->requiresPaymentReview($order),
            'canCancel' => ! in_array($status, [OrderStatus::Paid, OrderStatus::Cancelled], true),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => OrderStatus::Pending->value, 'label' => ProfileStatusLabels::orderStatus(OrderStatus::Pending)],
            ['value' => OrderStatus::Paid->value, 'label' => ProfileStatusLabels::orderStatus(OrderStatus::Paid)],
            ['value' => OrderStatus::Failed->value, 'label' => ProfileStatusLabels::orderStatus(OrderStatus::Failed)],
            ['value' => OrderStatus::InstallmentReview->value, 'label' => ProfileStatusLabels::orderStatus(OrderStatus::InstallmentReview)],
            ['value' => OrderStatus::ManualReview->value, 'label' => ProfileStatusLabels::orderStatus(OrderStatus::ManualReview)],
            ['value' => OrderStatus::Cancelled->value, 'label' => ProfileStatusLabels::orderStatus(OrderStatus::Cancelled)],
        ];
    }
}
