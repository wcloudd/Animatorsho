<?php

namespace App\Services\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SmsMessageStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Enums\SupportTicketStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SmsMessage;
use App\Models\SpotPlayerLicense;
use App\Models\SupportTicket;
use App\Support\ProfileStatusLabels;
use App\Support\SupportTicketStatusLabels;
use App\Support\TomanFormatter;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardService
{
    private const int ACTION_QUEUE_LIMIT = 5;

    private const int ACTIVITY_QUEUE_LIMIT = 2;

    public function __construct(
        private readonly AdminSmsService $sms,
    ) {}

    /**
     * @return array{
     *     summary: list<array{
     *         key: string,
     *         label: string,
     *         count: int,
     *         href: string,
     *         tone: 'warning'|'danger'|'neutral'
     *     }>,
     *     actionQueues: list<array{
     *         key: string,
     *         title: string,
     *         viewAllHref: string,
     *         items: list<array{
     *             id: int,
     *             title: string,
     *             subtitle: string,
     *             meta: string,
     *             href: string,
     *             badge: ?array{label: string, tone: string}
     *         }>
     *     }>,
     *     activityQueues: list<array{
     *         key: string,
     *         title: string,
     *         viewAllHref: string,
     *         items: list<array{
     *             id: int,
     *             title: string,
     *             subtitle: string,
     *             meta: string,
     *             href: string,
     *             badge: ?array{label: string, tone: string}
     *         }>
     *     }>,
     *     allActionQueuesEmpty: bool
     * }
     */
    public function forDashboard(): array
    {
        $actionQueues = $this->nonEmptyQueues([
            $this->pendingCardToCardQueue(),
            $this->pendingInstallmentQueue(),
            $this->pendingLicensesQueue(),
            $this->licenseApiFailuresQueue(),
            $this->openSupportTicketsQueue(),
        ]);

        $activityQueues = $this->nonEmptyQueues([
            $this->recentOrdersQueue(),
            $this->recentPaymentsQueue(),
            $this->recentSmsIssuesQueue(),
        ]);

        return [
            'summary' => $this->summaryCards(),
            'actionQueues' => $actionQueues,
            'activityQueues' => $activityQueues,
            'allActionQueuesEmpty' => $actionQueues === [],
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, href: string, tone: 'warning'|'danger'|'neutral'}>
     */
    private function summaryCards(): array
    {
        $pendingCardToCard = $this->pendingCardToCardQuery()->count();
        $pendingInstallment = $this->pendingInstallmentQuery()->count();
        $pendingLicenses = SpotPlayerLicense::query()
            ->where('status', SpotPlayerLicenseStatus::Pending)
            ->count();
        $licenseApiFailures = $this->licenseApiFailureQuery()->count();
        $openTickets = SupportTicket::query()
            ->where('status', SupportTicketStatus::Open)
            ->count();
        $smsIssues = $this->actionableSmsIssuesQuery()->count();

        return [
            $this->summaryCard(
                key: 'pending_card_to_card',
                label: 'کارت‌به‌کارت در انتظار',
                count: $pendingCardToCard,
                href: route('admin.payments.index', ['status' => PaymentStatus::Reviewing->value]),
            ),
            $this->summaryCard(
                key: 'pending_installment',
                label: 'اقساطی در انتظار',
                count: $pendingInstallment,
                href: route('admin.payments.index', ['status' => PaymentStatus::Reviewing->value]),
            ),
            $this->summaryCard(
                key: 'pending_licenses',
                label: 'لایسنس در انتظار فعال‌سازی',
                count: $pendingLicenses,
                href: route('admin.licenses.index'),
            ),
            $this->summaryCard(
                key: 'license_api_failures',
                label: 'خطای API لایسنس',
                count: $licenseApiFailures,
                href: route('admin.licenses.index'),
                danger: true,
            ),
            $this->summaryCard(
                key: 'open_support_tickets',
                label: 'تیکت باز',
                count: $openTickets,
                href: route('admin.support.index', ['status' => SupportTicketStatus::Open->value]),
            ),
            $this->summaryCard(
                key: 'sms_issues',
                label: 'خطای واقعی پیامک',
                count: $smsIssues,
                href: route('admin.sms.logs.index'),
            ),
        ];
    }

    /**
     * @return array{key: string, label: string, count: int, href: string, tone: 'warning'|'danger'|'neutral'}
     */
    private function summaryCard(
        string $key,
        string $label,
        int $count,
        string $href,
        bool $danger = false,
    ): array {
        $tone = 'neutral';

        if ($count > 0) {
            $tone = $danger ? 'danger' : 'warning';
        }

        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'href' => $href,
            'tone' => $tone,
        ];
    }

    /**
     * @param  list<array{
     *     key: string,
     *     title: string,
     *     viewAllHref: string,
     *     items: list<array<string, mixed>>
     * }>  $queues
     * @return list<array{
     *     key: string,
     *     title: string,
     *     viewAllHref: string,
     *     items: list<array<string, mixed>>
     * }>
     */
    private function nonEmptyQueues(array $queues): array
    {
        return array_values(array_filter(
            $queues,
            fn (array $queue): bool => $queue['items'] !== [],
        ));
    }

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     viewAllHref: string,
     *     items: list<array{
     *         id: int,
     *         title: string,
     *         subtitle: string,
     *         meta: string,
     *         href: string,
     *         badge: ?array{label: string, tone: string}
     *     }>
     * }
     */
    private function pendingCardToCardQueue(): array
    {
        $items = $this->pendingCardToCardQuery()
            ->with(['order.coursePackage'])
            ->limit(self::ACTION_QUEUE_LIMIT)
            ->get()
            ->map(fn (Payment $payment): array => $this->paymentQueueItem(
                $payment,
                route('admin.payments.index', ['status' => PaymentStatus::Reviewing->value]),
            ))
            ->values()
            ->all();

        return [
            'key' => 'pending_card_to_card',
            'title' => 'رسیدهای کارت‌به‌کارت',
            'viewAllHref' => route('admin.payments.index', ['status' => PaymentStatus::Reviewing->value]),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     viewAllHref: string,
     *     items: list<array{
     *         id: int,
     *         title: string,
     *         subtitle: string,
     *         meta: string,
     *         href: string,
     *         badge: ?array{label: string, tone: string}
     *     }>
     * }
     */
    private function pendingInstallmentQueue(): array
    {
        $items = $this->pendingInstallmentQuery()
            ->with(['order.coursePackage'])
            ->limit(self::ACTION_QUEUE_LIMIT)
            ->get()
            ->map(fn (Payment $payment): array => $this->paymentQueueItem(
                $payment,
                route('admin.payments.index', ['status' => PaymentStatus::Reviewing->value]),
            ))
            ->values()
            ->all();

        return [
            'key' => 'pending_installment',
            'title' => 'درخواست‌های اقساطی',
            'viewAllHref' => route('admin.payments.index', ['status' => PaymentStatus::Reviewing->value]),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     viewAllHref: string,
     *     items: list<array{
     *         id: int,
     *         title: string,
     *         subtitle: string,
     *         meta: string,
     *         href: string,
     *         badge: ?array{label: string, tone: string}
     *     }>
     * }
     */
    private function pendingLicensesQueue(): array
    {
        $items = SpotPlayerLicense::query()
            ->with(['coursePackage', 'order'])
            ->where('status', SpotPlayerLicenseStatus::Pending)
            ->latest()
            ->limit(self::ACTION_QUEUE_LIMIT)
            ->get()
            ->map(fn (SpotPlayerLicense $license): array => $this->licenseQueueItem($license))
            ->values()
            ->all();

        return [
            'key' => 'pending_licenses',
            'title' => 'لایسنس‌های در انتظار فعال‌سازی',
            'viewAllHref' => route('admin.licenses.index'),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     viewAllHref: string,
     *     items: list<array{
     *         id: int,
     *         title: string,
     *         subtitle: string,
     *         meta: string,
     *         href: string,
     *         badge: ?array{label: string, tone: string}
     *     }>
     * }
     */
    private function licenseApiFailuresQueue(): array
    {
        $items = $this->licenseApiFailureQuery()
            ->with(['coursePackage', 'order'])
            ->latest()
            ->limit(self::ACTION_QUEUE_LIMIT)
            ->get()
            ->map(fn (SpotPlayerLicense $license): array => $this->licenseQueueItem($license, true))
            ->values()
            ->all();

        return [
            'key' => 'license_api_failures',
            'title' => 'خطاهای API SpotPlayer',
            'viewAllHref' => route('admin.licenses.index'),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     viewAllHref: string,
     *     items: list<array{
     *         id: int,
     *         title: string,
     *         subtitle: string,
     *         meta: string,
     *         href: string,
     *         badge: ?array{label: string, tone: string}
     *     }>
     * }
     */
    private function openSupportTicketsQueue(): array
    {
        $items = SupportTicket::query()
            ->latest()
            ->where('status', SupportTicketStatus::Open)
            ->limit(self::ACTION_QUEUE_LIMIT)
            ->get()
            ->map(fn (SupportTicket $ticket): array => [
                'id' => $ticket->id,
                'title' => $ticket->subject,
                'subtitle' => $ticket->customer_name,
                'meta' => SupportTicketStatusLabels::category($ticket->category),
                'href' => route('admin.support.show', $ticket),
                'badge' => [
                    'label' => SupportTicketStatusLabels::status($ticket->status),
                    'tone' => SupportTicketStatusLabels::statusTone($ticket->status),
                ],
            ])
            ->values()
            ->all();

        return [
            'key' => 'open_support_tickets',
            'title' => 'تیکت‌های نیازمند پاسخ',
            'viewAllHref' => route('admin.support.index', ['status' => SupportTicketStatus::Open->value]),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     viewAllHref: string,
     *     items: list<array{
     *         id: int,
     *         title: string,
     *         subtitle: string,
     *         meta: string,
     *         href: string,
     *         badge: ?array{label: string, tone: string}
     *     }>
     * }
     */
    private function recentOrdersQueue(): array
    {
        $items = Order::query()
            ->with('coursePackage')
            ->latest()
            ->limit(self::ACTIVITY_QUEUE_LIMIT)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'title' => $order->order_number,
                'subtitle' => $order->coursePackage?->title ?? '—',
                'meta' => $order->customer_name ?? '—',
                'href' => route('admin.orders.index'),
                'badge' => [
                    'label' => ProfileStatusLabels::orderStatus($order->status),
                    'tone' => ProfileStatusLabels::orderStatusTone($order->status),
                ],
            ])
            ->values()
            ->all();

        return [
            'key' => 'recent_orders',
            'title' => 'آخرین سفارش‌ها',
            'viewAllHref' => route('admin.orders.index'),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     viewAllHref: string,
     *     items: list<array{
     *         id: int,
     *         title: string,
     *         subtitle: string,
     *         meta: string,
     *         href: string,
     *         badge: ?array{label: string, tone: string}
     *     }>
     * }
     */
    private function recentPaymentsQueue(): array
    {
        $items = Payment::query()
            ->with(['order.coursePackage'])
            ->latest()
            ->limit(self::ACTIVITY_QUEUE_LIMIT)
            ->get()
            ->map(fn (Payment $payment): array => $this->paymentQueueItem(
                $payment,
                route('admin.payments.index'),
            ))
            ->values()
            ->all();

        return [
            'key' => 'recent_payments',
            'title' => 'آخرین پرداخت‌ها',
            'viewAllHref' => route('admin.payments.index'),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     viewAllHref: string,
     *     items: list<array{
     *         id: int,
     *         title: string,
     *         subtitle: string,
     *         meta: string,
     *         href: string,
     *         badge: ?array{label: string, tone: string}
     *     }>
     * }
     */
    private function recentSmsIssuesQueue(): array
    {
        $items = $this->actionableSmsIssuesQuery()
            ->limit(self::ACTIVITY_QUEUE_LIMIT)
            ->get()
            ->map(function (SmsMessage $message): array {
                $logItem = $this->sms->toLogItem($message);

                return [
                    'id' => $message->id,
                    'title' => $logItem['type'],
                    'subtitle' => $logItem['mobile'] ?? '—',
                    'meta' => $this->smsIssueSummary($message),
                    'href' => route('admin.sms.logs.index'),
                    'badge' => [
                        'label' => $logItem['status'],
                        'tone' => $logItem['statusTone'],
                    ],
                ];
            })
            ->values()
            ->all();

        return [
            'key' => 'recent_sms_issues',
            'title' => 'خطاهای واقعی پیامک',
            'viewAllHref' => route('admin.sms.logs.index'),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     subtitle: string,
     *     meta: string,
     *     href: string,
     *     badge: array{label: string, tone: string}
     * }
     */
    private function paymentQueueItem(Payment $payment, string $href): array
    {
        $order = $payment->order;
        $status = $payment->status;

        return [
            'id' => $payment->id,
            'title' => $order?->order_number ?? '—',
            'subtitle' => $order?->coursePackage?->title ?? '—',
            'meta' => TomanFormatter::format($payment->amount_toman).' · '.ProfileStatusLabels::paymentMethod($payment->method),
            'href' => $href,
            'badge' => [
                'label' => ProfileStatusLabels::paymentStatus($status),
                'tone' => ProfileStatusLabels::paymentStatusTone($status),
            ],
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     subtitle: string,
     *     meta: string,
     *     href: string,
     *     badge: array{label: string, tone: string}
     * }
     */
    private function licenseQueueItem(SpotPlayerLicense $license, bool $includeApiError = false): array
    {
        $meta = is_array($license->meta) ? $license->meta : [];
        $apiError = is_string($meta['spotplayer_error_message'] ?? null)
            ? $meta['spotplayer_error_message']
            : (is_string($meta['last_api_error'] ?? null) ? $meta['last_api_error'] : null);

        $metaText = $license->order?->customer_name ?? '—';

        if ($includeApiError && $apiError !== null) {
            $metaText = mb_strlen($apiError) > 60 ? mb_substr($apiError, 0, 60).'…' : $apiError;
        }

        return [
            'id' => $license->id,
            'title' => $license->coursePackage?->title ?? '—',
            'subtitle' => $license->order?->order_number ?? '—',
            'meta' => $metaText,
            'href' => route('admin.licenses.index'),
            'badge' => [
                'label' => ProfileStatusLabels::licenseStatus($license->status),
                'tone' => ProfileStatusLabels::licenseStatusTone($license->status),
            ],
        ];
    }

    private function smsIssueSummary(SmsMessage $message): string
    {
        $meta = is_array($message->meta) ? $message->meta : [];
        $providerError = is_string($meta['provider_error'] ?? null) ? $meta['provider_error'] : null;

        return match ($providerError) {
            'send_rejected' => 'رد توسط سرویس پیامک',
            'connection_failed' => 'خطای اتصال به سرویس',
            'invalid_response' => 'پاسخ نامعتبر از سرویس',
            'configuration_missing' => 'پیکربندی سرویس ناقص',
            'driver_exception' => 'خطای غیرمنتظره در ارسال',
            'invalid_mobile' => 'شماره موبایل نامعتبر',
            default => 'خطای واقعی ارسال پیامک',
        };
    }

    /**
     * @return Builder<Payment>
     */
    private function pendingCardToCardQuery(): Builder
    {
        return Payment::query()
            ->where('status', PaymentStatus::Reviewing)
            ->where('method', PaymentMethod::CardToCard)
            ->latest();
    }

    /**
     * @return Builder<Payment>
     */
    private function pendingInstallmentQuery(): Builder
    {
        return Payment::query()
            ->where('status', PaymentStatus::Reviewing)
            ->where('method', PaymentMethod::Installment)
            ->latest();
    }

    /**
     * @return Builder<SpotPlayerLicense>
     */
    private function licenseApiFailureQuery(): Builder
    {
        return SpotPlayerLicense::query()
            ->where(function ($query): void {
                $query->where('status', SpotPlayerLicenseStatus::Failed)
                    ->orWhere(function ($query): void {
                        $query->where('status', SpotPlayerLicenseStatus::Pending)
                            ->where(function ($query): void {
                                $query->whereNotNull('meta->last_api_error')
                                    ->orWhereNotNull('meta->spotplayer_error_message');
                            });
                    });
            });
    }

    /**
     * @return Builder<SmsMessage>
     */
    private function actionableSmsIssuesQuery(): Builder
    {
        return SmsMessage::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->where(function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('status', SmsMessageStatus::Failed)
                        ->where(function (Builder $query): void {
                            $query->where('provider', '!=', 'log')
                                ->orWhereNotNull('meta->provider_error');
                        });
                });
            })
            ->latest();
    }
}
