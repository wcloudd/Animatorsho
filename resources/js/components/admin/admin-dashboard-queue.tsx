import { Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import { AdminTextLink } from '@/components/admin/admin-text-link';
import { surfaceCardClassName } from '@/components/page-container';
import type { AdminDashboardQueue } from '@/types/admin';
import type { AdminStatusTone } from '@/components/admin/admin-status-badge';
import { cn } from '@/lib/utils';

type AdminDashboardQueueSectionProps = {
    queue: AdminDashboardQueue;
    compact?: boolean;
    urgent?: boolean;
};

function QueueItemBadge({
    badge,
}: {
    badge: { label: string; tone: AdminStatusTone };
}) {
    return (
        <AdminStatusBadge tone={badge.tone}>{badge.label}</AdminStatusBadge>
    );
}

const queueItemToneStyles: Partial<Record<AdminStatusTone, string>> = {
    danger:
        'bg-red-soft/50 ring-red/20 hover:bg-red-soft/70 hover:ring-red/30',
    warning:
        'bg-gold-soft/50 ring-gold/20 hover:bg-gold-soft/70 hover:ring-gold/30',
};

function queueItemClassName(
    tone: AdminStatusTone | undefined,
    compact: boolean,
): string {
    return cn(
        'group flex flex-col gap-1.5 rounded-xl px-3 ring-1 transition',
        tone && queueItemToneStyles[tone]
            ? queueItemToneStyles[tone]
            : 'bg-bg ring-border/80 hover:bg-purple-soft/40 hover:ring-purple/20',
        compact ? 'py-2.5' : 'py-3',
    );
}

export function AdminDashboardQueueSection({
    queue,
    compact = false,
    urgent = false,
}: AdminDashboardQueueSectionProps) {
    return (
        <section
            className={cn(
                surfaceCardClassName,
                'flex flex-col gap-3 p-4',
                urgent && 'ring-gold/30',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <AdminSectionTitle className="mb-0">{queue.title}</AdminSectionTitle>
                <AdminTextLink href={queue.viewAllHref} className="text-xs">
                    مشاهده همه
                </AdminTextLink>
            </div>

            <ul className="flex flex-col gap-2">
                {queue.items.map((item) => (
                    <li key={item.id}>
                        <Link
                            href={item.href}
                            className={queueItemClassName(
                                item.badge?.tone,
                                compact,
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
                            <span className="flex items-center gap-1 text-xs font-bold text-purple">
                                بررسی
                                <ChevronLeft
                                    className="size-3.5 transition group-hover:-translate-x-0.5"
                                    aria-hidden
                                />
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}
