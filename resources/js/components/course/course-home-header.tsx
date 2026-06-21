import type { CourseHomeNotifications, CourseHomeProgress, CourseHomeWelcome } from '@/lib/course-home-data';
import { CourseNotificationsDrawer } from '@/components/course/course-notifications-drawer';

type CourseHomeHeaderProps = {
    welcome: CourseHomeWelcome;
    progress: CourseHomeProgress;
    notifications: CourseHomeNotifications;
};

export function CourseHomeHeader({
    welcome,
    progress,
    notifications,
}: CourseHomeHeaderProps) {
    return (
        <header className="relative overflow-hidden rounded-[28px] bg-surface px-5 py-5 shadow-soft ring-1 ring-border">
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-gradient-to-bl from-purple-soft via-surface to-gold-soft/40"
            />

            <div className="relative flex flex-col gap-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="flex min-w-0 flex-1 flex-col gap-1">
                        <span className="inline-flex w-fit items-center rounded-pill bg-purple-soft px-2.5 py-0.5 text-[11px] font-bold text-purple ring-1 ring-purple/15">
                            پنل هنرجو
                        </span>
                        <h1 className="font-display text-[1.35rem] leading-tight font-bold text-text">
                            سلام، {welcome.firstName}!
                        </h1>
                        <p className="text-xs font-medium text-muted">
                            باشگاه هنرجوی انیماتورشو
                        </p>
                    </div>

                    <CourseNotificationsDrawer notifications={notifications} />
                </div>

                <div className="flex flex-col gap-2 rounded-2xl bg-bg/80 px-3.5 py-3 ring-1 ring-border/60">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-bold text-text">
                            سطح {progress.level}
                        </span>
                        <span className="text-[11px] font-medium text-muted">
                            {progress.currentLevelXp} از {progress.xpPerLevel} XP
                            تا سطح بعدی
                        </span>
                    </div>
                    <div
                        className="h-2 overflow-hidden rounded-pill bg-surface ring-1 ring-border/50"
                        role="progressbar"
                        aria-valuenow={progress.progressPercent}
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-label="پیشرفت تا سطح بعد"
                    >
                        <div
                            className="h-full rounded-pill bg-gradient-to-l from-gold to-purple transition-all"
                            style={{ width: `${progress.progressPercent}%` }}
                        />
                    </div>
                    <p className="text-[11px] font-medium text-muted/70">
                        مجموع XP: {progress.totalXp}
                    </p>
                </div>
            </div>
        </header>
    );
}
