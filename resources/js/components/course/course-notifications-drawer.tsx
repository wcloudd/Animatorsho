import { Link, router } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import type { CourseHomeNotificationItem, CourseHomeNotifications } from '@/lib/course-home-data';
import { cn } from '@/lib/utils';

type Props = {
    notifications: CourseHomeNotifications;
};

export function CourseNotificationsDrawer({ notifications }: Props) {
    const { items, unreadCount } = notifications;

    function handleMarkAllRead(): void {
        router.post('/course/notifications/read-all', {}, { preserveScroll: true });
    }

    function handleActionClick(
        e: React.MouseEvent,
        item: CourseHomeNotificationItem,
    ): void {
        if (item.isUnread && item.actionUrl) {
            e.preventDefault();
            router.patch(
                `/course/notifications/${item.id}/read`,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.visit(item.actionUrl!);
                    },
                },
            );
        }
    }

    return (
        <Sheet>
            <SheetTrigger asChild>
                <button
                    type="button"
                    aria-label="اعلان‌ها"
                    className="relative flex size-10 shrink-0 items-center justify-center rounded-2xl bg-bg ring-1 ring-border/70 transition-colors hover:bg-purple-soft/50"
                >
                    <Bell className="size-[18px] text-purple" />
                    {unreadCount > 0 ? (
                        <span className="absolute -start-1 -top-1 flex size-4 items-center justify-center rounded-full bg-red text-[10px] font-bold text-white">
                            {unreadCount}
                        </span>
                    ) : null}
                </button>
            </SheetTrigger>

            <SheetContent
                side="bottom"
                className="max-h-[85dvh] rounded-t-[24px] border-t-0 bg-surface px-0"
            >
                <SheetHeader className="flex-row items-center justify-between gap-3 border-b border-border/60 py-4 pr-12 pl-4">
                    <div className="flex items-center gap-2">
                        <Bell className="size-4 text-purple" />
                        <SheetTitle className="text-sm font-bold text-text">
                            اعلان‌ها
                        </SheetTitle>
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
                            className="flex shrink-0 items-center gap-1 text-[11px] font-medium text-purple underline-offset-2 hover:underline"
                        >
                            <CheckCheck className="size-3.5" />
                            همه را خوانده نشان بده
                        </button>
                    ) : null}
                </SheetHeader>

                <div className="overflow-y-auto px-4 py-4" style={{ maxHeight: 'calc(85dvh - 65px)' }}>
                    {items.length === 0 ? (
                        <p className="py-8 text-center text-xs font-medium text-muted">
                            فعلاً اعلان جدیدی نداری.
                        </p>
                    ) : (
                        <>
                        <p className="mb-3 text-[11px] font-semibold text-muted">آخرین اعلان‌ها</p>
                        <ul className="flex flex-col gap-2">
                            {items.map((item) => (
                                <li
                                    key={item.id}
                                    className={cn(
                                        'flex flex-col gap-1.5 rounded-2xl px-3.5 py-3 ring-1',
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
                                            onClick={(e) => handleActionClick(e, item)}
                                        >
                                            {item.actionLabel ?? 'مشاهده'}
                                        </Link>
                                    ) : null}
                                </li>
                            ))}
                        </ul>
                        </>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}
