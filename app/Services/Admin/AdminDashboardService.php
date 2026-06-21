<?php

namespace App\Services\Admin;

use App\Enums\ConsultationRequestStatus;
use App\Enums\ExerciseSubmissionStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SmsMessageStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Enums\SupportTicketStatus;
use App\Models\ConsultationRequest;
use App\Models\ExerciseSubmission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SecurityEvent;
use App\Models\SmsMessage;
use App\Models\SpotPlayerLicense;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\AdminStatusLabels;
use App\Support\ConsultationRequestStatusLabels;
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
        private readonly AdminFinanceSummaryService $financeSummary,
    ) {}

    /**
     * @return array{
     *     activityMetrics: list<array{
     *         key: string,
     *         label: string,
     *         count: int,
     *         href: string|null,
     *         tone: 'warning'|'danger'|'neutral'
     *     }>,
     *     securityEventsLast24Hours: int,
     *     summary: list<array{
     *         key: string,
     *         label: string,
     *         count: int,
     *         href: string|null,
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
     *     allActionQueuesEmpty: bool,
     *     financeSummary: array{
     *         confirmedRevenueTotal: int,
     *         confirmedRevenueTotalFormatted: string,
     *         confirmedRevenueToday: int,
     *         confirmedRevenueTodayFormatted: string,
     *         confirmedRevenueCurrentMonth: int,
     *         confirmedRevenueCurrentMonthFormatted: string,
     *         successfulPaymentsCount: int,
     *         pendingPaymentsCount: int,
     *         failedOrCancelledCount: int,
     *         reviewingCardToCardCount: int,
     *         reviewingCardToCardAmount: int,
     *         reviewingCardToCardAmountFormatted: string,
     *         reviewingInstallmentCount: int,
     *         reviewingInstallmentAmount: int,
     *         reviewingInstallmentAmountFormatted: string,
     *         paidByMethod: list<array{
     *             method: string,
     *             label: string,
     *             count: int,
     *             amountToman: int,
     *             amountFormatted: string
     *         }>,
     *         topPackages: list<array{
     *             packageId: int,
     *             title: string,
     *             paidCount: int,
     *             revenueToman: int,
     *             revenueFormatted: string
     *         }>,
     *         externalGrantsCount: int,
     *         externalGrantsAmount: int,
     *         externalGrantsAmountFormatted: string,
     *         activeLicensesCount: int
     *     }
     * }
     */
    public function forDashboard(): array
    {
        $actionQueues = $this->nonEmptyQueues([
            $this->pendingExerciseSubmissionsQueue(),
            $this->pendingCardToCardQueue(),
            $this->pendingInstallmentQueue(),
            $this->newConsultationsQueue(),
            $this->followUpConsultationsQueue(),
            $this->openSupportTicketsQueue(),
            $this->pendingLicensesQueue(),
            $this->licenseApiFailuresQueue(),
        ]);

        $activityQueues = $this->nonEmptyQueues([
            $this->recentOrdersQueue(),
            $this->recentPaymentsQueue(),
            $this->recentSmsIssuesQueue(),
        ]);

        return [
            'activityMetrics' => $this->activityMetrics(),
            'securityEventsLast24Hours' => SecurityEvent::query()
                ->where('occurred_at', '>=', now()->subDay())
                ->count(),
            'summary' => $this->summaryCards(),
            'actionQueues' => $actionQueues,
            'activityQueues' => $activityQueues,
            'allActionQueuesEmpty' => $actionQueues === [],
            'financeSummary' => $this->financeSummary->forDashboard(),
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, href: string|null, tone: 'warning'|'danger'|'neutral'}>
     */
    private function activityMetrics(): array
    {
        $startOfToday = now()->startOfDay();
        $startOfSevenDayWindow = now()->subDays(7)->startOfDay();

        return [
            $this->summaryCard(
                key: 'registrations_today',
                label: 'ثبت‌نام امروز',
                count: User::query()->where('created_at', '>=', $startOfToday)->count(),
                href: null,
                informational: true,
            ),
            $this->summaryCard(
                key: 'registrations_last_7_days',
                label: 'ثبت‌نام ۷ روز اخیر',
                count: User::query()->where('created_at', '>=', $startOfSevenDayWindow)->count(),
                href: null,
                informational: true,
            ),
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, href: string|null, tone: 'warning'|'danger'|'neutral'}>
     */
    private function summaryCards(): array
    {
        $pendingExerciseSubmissions = ExerciseSubmission::query()
            ->whereIn('status', [ExerciseSubmissionStatus::Submitted, ExerciseSubmissionStatus::Reviewing])
            ->count();
        $pendingCardToCard = $this->pendingCardToCardQuery()->count();
        $pendingInstallment = $this->pendingInstallmentQuery()->count();
        $newConsultations = ConsultationRequest::query()
            ->where('status', ConsultationRequestStatus::New)
            ->count();
        $followUpConsultations = ConsultationRequest::query()
            ->where('status', ConsultationRequestStatus::FollowUp)
            ->count();
        $pendingLicenses = SpotPlayerLicense::query()
            ->where('status', SpotPlayerLicenseStatus::Pending)
            ->count();
        $licenseApiFailures = $this->licenseApiFailureQuery()->count();
        $openTickets = SupportTicket::query()
            ->where('status', SupportTicketStatus::Open)
            ->count();
        $supportWaitingUser = SupportTicket::query()
            ->where('status', SupportTicketStatus::WaitingUser)
            ->count();
        $smsIssues = $this->actionableSmsIssuesQuery()->count();

        return [
            $this->summaryCard(
                key: 'exercise_submissions_pending',
                label: 'تمرین نیازمند بررسی',
                count: $pendingExerciseSubmissions,
                href: route('admin.exercise-submissions.index', ['status' => ExerciseSubmissionStatus::Submitted->value]),
            ),
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
                href: route('admin.installments.index', ['status' => 'awaiting_review']),
            ),
            $this->summaryCard(
                key: 'new_consultations',
                label: 'مشاوره جدید',
                count: $newConsultations,
                href: route('admin.consultations.index', ['status' => ConsultationRequestStatus::New->value]),
            ),
            $this->summaryCard(
                key: 'follow_up_consultations',
                label: 'مشاوره نیازمند پیگیری',
                count: $followUpConsultations,
                href: route('admin.consultations.index', ['status' => ConsultationRequestStatus::FollowUp->value]),
            ),
            $this->summaryCard(
                key: 'open_support_tickets',
                label: 'تیکت باز',
                count: $openTickets,
                href: route('admin.support.index', ['status' => SupportTicketStatus::Open->value]),
            ),
            $this->summaryCard(
                key: 'support_waiting_user',
                label: 'تیکت منتظر کاربر',
                count: $supportWaitingUser,
                href: route('admin.support.index', ['status' => SupportTicketStatus::WaitingUser->value]),
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
                key: 'sms_issues',
                label: 'خطای واقعی پیامک',
                count: $smsIssues,
                href: route('admin.sms.logs.index'),
            ),
        ];
    }

    /**
     * @return array{key: string, label: string, count: int, href: string|null, tone: 'warning'|'danger'|'neutral'}
     */
    private function summaryCard(
        string $key,
        string $label,
        int $count,
        ?string $href,
        bool $danger = false,
        bool $informational = false,
    ): array {
        $tone = 'neutral';

        if (! $informational && $count > 0) {
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
    private function pendingExerciseSubmissionsQueue(): array
    {
        $items = ExerciseSubmission::query()
            ->with('user')
            ->whereIn('status', [ExerciseSubmissionStatus::Submitted, ExerciseSubmissionStatus::Reviewing])
            ->latest()
            ->limit(self::ACTION_QUEUE_LIMIT)
            ->get()
            ->map(fn (ExerciseSubmission $submission): array => [
                'id' => $submission->id,
                'title' => $submission->title,
                'subtitle' => $submission->user?->name ?? '—',
                'meta' => match ($submission->status) {
                    ExerciseSubmissionStatus::Submitted => 'ارسال‌شده',
                    ExerciseSubmissionStatus::Reviewing => 'در حال بررسی',
                    default => '—',
                },
                'href' => route('admin.exercise-submissions.show', $submission),
                'badge' => [
                    'label' => match ($submission->status) {
                        ExerciseSubmissionStatus::Submitted => 'ارسال‌شده',
                        ExerciseSubmissionStatus::Reviewing => 'در بررسی',
                        default => '—',
                    },
                    'tone' => match ($submission->status) {
                        ExerciseSubmissionStatus::Submitted => 'warning',
                        ExerciseSubmissionStatus::Reviewing => 'neutral',
                        default => 'neutral',
                    },
                ],
            ])
            ->values()
            ->all();

        return [
            'key' => 'pending_exercise_submissions',
            'title' => 'تمرین‌های نیازمند بررسی',
            'viewAllHref' => route('admin.exercise-submissions.index', ['status' => ExerciseSubmissionStatus::Submitted->value]),
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
    private function pendingCardToCardQueue(): array
    {
        $items = $this->pendingCardToCardQuery()
            ->with(['order.coursePackage'])
            ->limit(self::ACTION_QUEUE_LIMIT)
            ->get()
            ->map(fn (Payment $payment): array => $this->paymentQueueItem(
                $payment,
                $this->paymentFocusHref($payment),
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
                route('admin.installments.index', [
                    'status' => 'awaiting_review',
                    'focus' => $payment->order_id,
                ]),
            ))
            ->values()
            ->all();

        return [
            'key' => 'pending_installment',
            'title' => 'درخواست‌های اقساطی',
            'viewAllHref' => route('admin.installments.index', ['status' => 'awaiting_review']),
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
    private function newConsultationsQueue(): array
    {
        $items = ConsultationRequest::query()
            ->where('status', ConsultationRequestStatus::New)
            ->latest()
            ->limit(self::ACTION_QUEUE_LIMIT)
            ->get()
            ->map(fn (ConsultationRequest $request): array => $this->consultationQueueItem($request))
            ->values()
            ->all();

        return [
            'key' => 'new_consultations',
            'title' => 'درخواست‌های مشاوره جدید',
            'viewAllHref' => route('admin.consultations.index', ['status' => ConsultationRequestStatus::New->value]),
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
    private function followUpConsultationsQueue(): array
    {
        $items = ConsultationRequest::query()
            ->where('status', ConsultationRequestStatus::FollowUp)
            ->latest()
            ->limit(self::ACTION_QUEUE_LIMIT)
            ->get()
            ->map(fn (ConsultationRequest $request): array => $this->consultationQueueItem($request))
            ->values()
            ->all();

        return [
            'key' => 'follow_up_consultations',
            'title' => 'مشاوره‌های نیازمند پیگیری',
            'viewAllHref' => route('admin.consultations.index', ['status' => ConsultationRequestStatus::FollowUp->value]),
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
                    'tone' => AdminStatusLabels::orderStatusTone($order->status),
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
    private function consultationQueueItem(ConsultationRequest $request): array
    {
        $interest = ConsultationRequestStatusLabels::interest($request->interest);

        return [
            'id' => $request->id,
            'title' => $request->name,
            'subtitle' => $request->mobile,
            'meta' => $interest ?? ConsultationRequestStatusLabels::level($request->level) ?? '—',
            'href' => route('admin.consultations.index', [
                'status' => $request->status->value,
                'q' => $request->mobile,
            ]),
            'badge' => [
                'label' => ConsultationRequestStatusLabels::status($request->status),
                'tone' => ConsultationRequestStatusLabels::statusTone($request->status),
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
                'tone' => AdminStatusLabels::paymentStatusTone($status),
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
            'href' => route('admin.licenses.index', ['focus' => $license->id]),
            'badge' => [
                'label' => ProfileStatusLabels::licenseStatus($license->status),
                'tone' => AdminStatusLabels::licenseStatusTone($license->status),
            ],
        ];
    }

    private function paymentFocusHref(Payment $payment): string
    {
        return route('admin.payments.index', [
            'status' => PaymentStatus::Reviewing->value,
            'focus' => $payment->id,
        ]);
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
