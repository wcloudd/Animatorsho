import { Head } from '@inertiajs/react';
import { AdminDashboardQueueSection } from '@/components/admin/admin-dashboard-queue';
import { AdminDashboardSummaryCardLink } from '@/components/admin/admin-dashboard-summary-card';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import type { AdminDashboardPageProps } from '@/types/admin';

export default function AdminDashboard({
    activityMetrics,
    loginMetricsNote,
    summary,
    actionQueues,
    activityQueues,
    allActionQueuesEmpty,
}: AdminDashboardPageProps) {
    return (
        <>
            <Head title="داشبورد مدیریت" />
            <AdminPageHeader
                title="داشبورد مدیریت"
                description="کارهای امروز و موارد نیازمند بررسی"
            />

            <section className="mb-6">
                <AdminSectionTitle>آمار ثبت‌نام</AdminSectionTitle>
                <div className="grid grid-cols-2 gap-2.5">
                    {activityMetrics.map((card) => (
                        <AdminDashboardSummaryCardLink
                            key={card.key}
                            card={card}
                        />
                    ))}
                </div>
                <p className="mt-3 rounded-xl bg-purple-soft px-3.5 py-3 text-sm text-muted ring-1 ring-purple/10">
                    {loginMetricsNote}
                </p>
            </section>

            <section className="mb-6">
                <AdminSectionTitle>خلاصه وضعیت</AdminSectionTitle>
                <div className="grid grid-cols-2 gap-2.5">
                    {summary.map((card) => (
                        <AdminDashboardSummaryCardLink
                            key={card.key}
                            card={card}
                        />
                    ))}
                </div>
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
                    {activityQueues.map((queue) => (
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
