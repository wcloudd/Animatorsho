import { Link } from '@inertiajs/react';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import { surfaceCardClassName } from '@/components/page-container';
import type { AdminDashboardQueue } from '@/types/admin';
import type { ProfileStatusTone } from '@/lib/profile-data';
import { cn } from '@/lib/utils';

type AdminDashboardQueueSectionProps = {
    queue: AdminDashboardQueue;
    compact?: boolean;
};

function QueueItemBadge({
    badge,
}: {
    badge: { label: string; tone: ProfileStatusTone };
}) {
    return (
        <AdminStatusBadge tone={badge.tone}>{badge.label}</AdminStatusBadge>
    );
}

export function AdminDashboardQueueSection({
    queue,
    compact = false,
}: AdminDashboardQueueSectionProps) {
    return (
        <section className={cn(surfaceCardClassName, 'flex flex-col gap-3')}>
            <div className="flex items-start justify-between gap-3">
                <h3 className="font-bold text-text">{queue.title}</h3>
                <Link
                    href={queue.viewAllHref}
                    className="shrink-0 text-xs font-bold text-purple underline-offset-2 hover:underline"
                >
                    مشاهده همه
                </Link>
            </div>

            <ul className="flex flex-col gap-2">
                {queue.items.map((item) => (
                    <li key={item.id}>
                        <Link
                            href={item.href}
                            className={cn(
                                'flex flex-col gap-1.5 rounded-xl bg-bg px-3 ring-1 ring-border transition hover:bg-purple-soft/30',
                                compact ? 'py-2.5' : 'py-3',
                            )}
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-bold text-text">
                                        {item.title}
                                    </p>
                                    <p className="mt-0.5 truncate text-sm text-muted">
                                        {item.subtitle}
                                    </p>
                                </div>
                                {item.badge ? (
                                    <QueueItemBadge badge={item.badge} />
                                ) : null}
                            </div>
                            <p className="truncate text-xs text-muted">
                                {item.meta}
                            </p>
                            <span className="text-xs font-bold text-purple">
                                بررسی
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}
