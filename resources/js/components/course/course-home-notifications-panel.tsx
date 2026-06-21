import { Link, router } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import type { CourseHomeNotifications } from '@/lib/course-home-data';
import { cn } from '@/lib/utils';

type CourseHomeNotificationsPanelProps = {
    notifications: CourseHomeNotifications;
};

export function CourseHomeNotificationsPanel({
    notifications,
}: CourseHomeNotificationsPanelProps) {
    const { items, unreadCount } = notifications;

    function handleMarkAllRead(): void {
        router.post('/course/notifications/read-all', {}, { preserveScroll: true });
    }

    return (
        <section className="flex flex-col gap-3 rounded-[28px] bg-surface px-5 py-5 shadow-soft ring-1 ring-border">
            <div className="flex items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                    <Bell className="size-4 text-purple" />
                    <span className="text-sm font-bold text-text">اعلان‌ها</span>
                    {unreadCount > 0 ? (
                        <span className="inline-flex min-w-[20px] items-center justify-center rounded-full bg-red px-1.5 py-0.5 text-[10px] font-bold text-white">
                            {unreadCount}
                        </span>
                    ) : null}
                </div>

                {unreadCount > 0 ? (
                    <button
                        type="button"
                        onClick={handleMarkAllRead}
                        className="text-[11px] font-medium text-purple underline-offset-2 hover:underline"
                    >
                        همه را خوانده نشان بده
                    </button>
                ) : null}
            </div>

            {items.length === 0 ? (
                <p className="text-xs font-medium text-muted">
                    فعلاً اعلان جدیدی نداری.
                </p>
            ) : (
                <ul className="flex flex-col gap-2">
                    {items.map((item) => (
                        <li
                            key={item.id}
                            className={cn(
                                'flex flex-col gap-1.5 rounded-2xl px-3 py-3 ring-1',
                                item.isUnread
                                    ? 'bg-purple-soft/40 ring-purple/15'
                                    : 'bg-bg ring-border/50',
                            )}
                        >
                            <div className="flex items-start justify-between gap-2">
                                <span
                                    className={cn(
                                        'text-xs leading-snug',
                                        item.isUnread
                                            ? 'font-bold text-text'
                                            : 'font-medium text-muted',
                                    )}
                                >
                                    {item.title}
                                </span>
                                <span className="shrink-0 text-[10px] font-medium text-muted">
                                    {item.createdAtLabel}
                                </span>
                            </div>

                            {item.body ? (
                                <p className="text-[11px] font-medium leading-relaxed text-muted">
                                    {item.body}
                                </p>
                            ) : null}

                            {item.actionUrl ? (
                                <Link
                                    href={item.actionUrl}
                                    className="self-start text-[11px] font-bold text-purple"
                                >
                                    {item.actionLabel ?? 'مشاهده'}
                                </Link>
                            ) : null}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
