import { Head, Link } from '@inertiajs/react';
import { Shield } from 'lucide-react';
import { AdminDashboardQueueSection } from '@/components/admin/admin-dashboard-queue';
import { AdminFinanceSummaryPanel } from '@/components/admin/admin-finance-summary-panel';
import { AdminDashboardSummaryCardLink } from '@/components/admin/admin-dashboard-summary-card';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import type {
    AdminDashboardPageProps,
    AdminDashboardQueue,
    AdminDashboardSummaryCard,
} from '@/types/admin';
import { cn } from '@/lib/utils';

const overviewSummaryOrder = [
    'pending_card_to_card',
    'pending_installment',
    'open_support_tickets',
    'pending_licenses',
    'license_api_failures',
    'sms_issues',
    'new_consultations',
    'follow_up_consultations',
    'support_waiting_user',
] as const;

const registrationMetricKeys = [
    'registrations_today',
    'registrations_last_7_days',
] as const;

const financeQueueKeys = ['recent_orders', 'recent_payments'] as const;

function orderSummaryCards(
    cards: AdminDashboardSummaryCard[],
    keys: readonly string[],
): AdminDashboardSummaryCard[] {
    return keys
        .map((key) => cards.find((card) => card.key === key))
        .filter((card): card is AdminDashboardSummaryCard => card !== undefined);
}

function pickQueues(
    queues: AdminDashboardQueue[],
    keys: readonly string[],
): AdminDashboardQueue[] {
    return queues.filter((queue) =>
        (keys as readonly string[]).includes(queue.key),
    );
}

function AdminSecurityEventsCard({ count }: { count: number }) {
    return (
        <Link
            href="/admin/security-events"
            className={cn(
                'flex min-h-[5.75rem] flex-col justify-between rounded-2xl px-3.5 py-3 shadow-soft ring-1 transition hover:ring-purple/25',
                count > 0
                    ? 'bg-gold-soft ring-gold/30'
                    : 'bg-surface-warm ring-purple/10',
            )}
        >
            <div className="flex items-start justify-between gap-2">
                <span
                    className={cn(
                        'text-xs font-medium leading-relaxed',
                        count > 0 ? 'text-gold' : 'text-muted',
                    )}
                >
                    رویدادهای امنیتی (۲۴ ساعت)
                </span>
                <Shield
                    className={cn(
                        'size-4 shrink-0 opacity-70',
                        count > 0 ? 'text-gold' : 'text-muted',
                    )}
                    strokeWidth={1.75}
                    aria-hidden
                />
            </div>
            <span
                className={cn(
                    'font-liana text-2xl leading-none',
                    count > 0 ? 'text-gold' : 'text-purple',
                )}
            >
                {count.toLocaleString('fa-IR')}
            </span>
        </Link>
    );
}

export default function AdminDashboard({
    summary,
    actionQueues,
    activityQueues,
    activityMetrics,
    securityEventsLast24Hours,
    financeSummary,
    allActionQueuesEmpty,
}: AdminDashboardPageProps) {
    const overviewCards = [
        ...orderSummaryCards(summary, overviewSummaryOrder),
        ...orderSummaryCards(activityMetrics, registrationMetricKeys),
    ];
    const financeQueues = pickQueues(activityQueues, financeQueueKeys);
    const otherActivityQueues = activityQueues.filter(
        (queue) => !(financeQueueKeys as readonly string[]).includes(queue.key),
    );

    return (
        <>
            <Head title="داشبورد مدیریت" />
            <AdminPageHeader
                title="داشبورد مدیریت"
                description="خلاصه وضعیت و موارد نیازمند بررسی"
            />

            <section className="mb-6">
                <AdminSectionTitle>خلاصه</AdminSectionTitle>
                <div className="grid grid-cols-2 gap-2.5">
                    {overviewCards.map((card) => (
                        <AdminDashboardSummaryCardLink
                            key={card.key}
                            card={card}
                        />
                    ))}
                    <AdminSecurityEventsCard
                        count={securityEventsLast24Hours}
                    />
                </div>
            </section>

            <section className="mb-6">
                <AdminSectionTitle>مالی</AdminSectionTitle>
                <AdminFinanceSummaryPanel financeSummary={financeSummary} />
            </section>

            <section className="mb-6 flex flex-col gap-3">
                <AdminSectionTitle className="mb-0">
                    نیازمند اقدام
                </AdminSectionTitle>

                {allActionQueuesEmpty ? (
                    <AdminEmptyState message="همه چیز مرتب است — مورد فوری برای بررسی وجود ندارد." />
                ) : (
                    actionQueues.map((queue) => (
                        <AdminDashboardQueueSection
                            key={queue.key}
                            queue={queue}
                            urgent
                        />
                    ))
                )}
            </section>

            {activityQueues.length > 0 ? (
                <section className="flex flex-col gap-3">
                    <AdminSectionTitle className="mb-0">
                        فعالیت اخیر
                    </AdminSectionTitle>

                    {financeQueues.length > 0 ? (
                        <div className="flex flex-col gap-2">
                            <p className="text-[11px] font-semibold text-muted">
                                سفارش و پرداخت اخیر
                            </p>
                            {financeQueues.map((queue) => (
                                <AdminDashboardQueueSection
                                    key={queue.key}
                                    queue={queue}
                                    compact
                                />
                            ))}
                        </div>
                    ) : null}

                    {otherActivityQueues.map((queue) => (
                        <AdminDashboardQueueSection
                            key={queue.key}
                            queue={queue}
                            compact
                        />
                    ))}
                </section>
            ) : null}
        </>
    );
}
