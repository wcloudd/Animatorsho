import { Head, Link } from '@inertiajs/react';
import { KeyRound, Package, Shield, UserPlus } from 'lucide-react';
import { AdminDashboardQueueSection } from '@/components/admin/admin-dashboard-queue';
import { AdminDashboardSummaryCardLink } from '@/components/admin/admin-dashboard-summary-card';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import {
    partitionAdminDashboardProps,
} from '@/lib/admin-dashboard-sections';
import type { AdminDashboardPageProps } from '@/types/admin';
import { cn } from '@/lib/utils';

function AdminDashboardQuickLink({
    href,
    label,
    icon: Icon,
}: {
    href: string;
    label: string;
    icon: typeof UserPlus;
}) {
    return (
        <Link
            href={href}
            className="flex items-center gap-2 rounded-xl bg-surface px-3 py-2.5 text-sm font-medium text-text ring-1 ring-purple/10 transition hover:bg-purple-soft hover:text-purple"
        >
            <Icon className="size-4 shrink-0 text-purple/80" aria-hidden />
            <span>{label}</span>
        </Link>
    );
}

function AdminSecurityEventsCard({
    count,
}: {
    count: number;
}) {
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
                    رویدادهای امنیتی (۲۴ ساعت اخیر)
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
    dashboardSections,
}: AdminDashboardPageProps) {
    const sections = partitionAdminDashboardProps({
        summary,
        actionQueues,
        activityQueues,
        activityMetrics,
    });

    return (
        <>
            <Head title="داشبورد مدیریت" />
            <AdminPageHeader
                title="داشبورد مدیریت"
                description="کارهای امروز و موارد نیازمند بررسی"
            />

            <section
                id="section-action-required"
                className="mb-6 flex flex-col gap-3"
            >
                <AdminSectionTitle className="mb-0">
                    {dashboardSections.actionRequired}
                </AdminSectionTitle>

                {sections.actionRequired.summary.length > 0 ? (
                    <div className="grid grid-cols-2 gap-2.5">
                        {sections.actionRequired.summary.map((card) => (
                            <AdminDashboardSummaryCardLink
                                key={card.key}
                                card={card}
                            />
                        ))}
                    </div>
                ) : null}

                {!sections.actionRequired.hasUrgentAction ? (
                    <AdminEmptyState message="همه چیز مرتب است — مورد فوری برای بررسی وجود ندارد." />
                ) : (
                    sections.actionRequired.queues.map((queue) => (
                        <AdminDashboardQueueSection
                            key={queue.key}
                            queue={queue}
                            urgent
                        />
                    ))
                )}
            </section>

            <section id="section-finance" className="mb-6 flex flex-col gap-3">
                <AdminSectionTitle className="mb-0">
                    {dashboardSections.finance}
                </AdminSectionTitle>

                {sections.finance.queues.length > 0 ? (
                    sections.finance.queues.map((queue) => (
                        <AdminDashboardQueueSection
                            key={queue.key}
                            queue={queue}
                            compact
                        />
                    ))
                ) : (
                    <AdminEmptyState message="فعالیت مالی اخیر ثبت نشده است." />
                )}

                <p className="rounded-xl bg-purple-soft/50 px-3.5 py-3 text-sm text-muted ring-1 ring-purple/10">
                    گزارش مالی کامل در مرحله بعد اضافه می‌شود.
                </p>
            </section>

            <section id="section-learners" className="mb-6">
                <AdminSectionTitle>
                    {dashboardSections.learners}
                </AdminSectionTitle>
                <div className="grid grid-cols-2 gap-2.5">
                    {sections.learners.metrics.map((card) => (
                        <AdminDashboardSummaryCardLink
                            key={card.key}
                            card={card}
                        />
                    ))}
                </div>
                <div className="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <AdminDashboardQuickLink
                        href="/admin/manual-enrollments"
                        label="کاربران و دسترسی‌ها"
                        icon={UserPlus}
                    />
                    <AdminDashboardQuickLink
                        href="/admin/licenses"
                        label="لایسنس‌ها"
                        icon={KeyRound}
                    />
                    <AdminDashboardQuickLink
                        href="/admin/packages"
                        label="بسته‌ها"
                        icon={Package}
                    />
                </div>
            </section>

            <section
                id="section-communications"
                className="mb-6 flex flex-col gap-3"
            >
                <AdminSectionTitle className="mb-0">
                    {dashboardSections.communications}
                </AdminSectionTitle>

                {sections.communications.summary.length > 0 ? (
                    <div className="grid grid-cols-2 gap-2.5">
                        {sections.communications.summary.map((card) => (
                            <AdminDashboardSummaryCardLink
                                key={card.key}
                                card={card}
                            />
                        ))}
                    </div>
                ) : null}

                {sections.communications.queues.length > 0 ? (
                    sections.communications.queues.map((queue) => (
                        <AdminDashboardQueueSection
                            key={queue.key}
                            queue={queue}
                            urgent
                        />
                    ))
                ) : (
                    <AdminEmptyState message="درخواست مشاوره یا تیکت جدیدی برای نمایش وجود ندارد." />
                )}

                <p className="rounded-xl bg-purple-soft/30 px-3.5 py-2.5 text-xs text-muted ring-1 ring-purple/10">
                    تمرین‌ها و پیام استاد — به‌زودی
                </p>
            </section>

            <section id="section-security" className="flex flex-col gap-3">
                <AdminSectionTitle className="mb-0">
                    {dashboardSections.security}
                </AdminSectionTitle>

                <div className="grid grid-cols-2 gap-2.5">
                    <AdminSecurityEventsCard count={securityEventsLast24Hours} />
                    {sections.security.summary.map((card) => (
                        <AdminDashboardSummaryCardLink
                            key={card.key}
                            card={card}
                        />
                    ))}
                </div>

                {sections.security.queues.map((queue) => (
                    <AdminDashboardQueueSection
                        key={queue.key}
                        queue={queue}
                        compact
                    />
                ))}
            </section>
        </>
    );
}
