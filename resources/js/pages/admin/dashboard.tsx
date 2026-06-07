import { Head } from '@inertiajs/react';
import { AdminDashboardQueueSection } from '@/components/admin/admin-dashboard-queue';
import { AdminDashboardSummaryCardLink } from '@/components/admin/admin-dashboard-summary-card';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { surfaceCardClassName } from '@/components/page-container';
import type { AdminDashboardPageProps } from '@/types/admin';
import { cn } from '@/lib/utils';

export default function AdminDashboard({
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

            <section className="mb-5">
                <h3 className="mb-3 text-sm font-bold text-text">
                    خلاصه وضعیت
                </h3>
                <div className="grid grid-cols-2 gap-2">
                    {summary.map((card) => (
                        <AdminDashboardSummaryCardLink
                            key={card.key}
                            card={card}
                        />
                    ))}
                </div>
            </section>

            <section className="mb-5 flex flex-col gap-3">
                <h3 className="text-sm font-bold text-text">نیازمند اقدام</h3>

                {allActionQueuesEmpty ? (
                    <p
                        className={cn(
                            surfaceCardClassName,
                            'py-4 text-center text-sm font-medium text-green',
                        )}
                    >
                        مورد فوری برای بررسی وجود ندارد.
                    </p>
                ) : (
                    actionQueues.map((queue) => (
                        <AdminDashboardQueueSection
                            key={queue.key}
                            queue={queue}
                        />
                    ))
                )}
            </section>

            {activityQueues.length > 0 ? (
                <section className="flex flex-col gap-3">
                    <h3 className="text-sm font-bold text-text">
                        فعالیت اخیر
                    </h3>
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
