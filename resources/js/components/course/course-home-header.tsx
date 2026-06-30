import { Award, BookOpen, Crown, ImagePlay, PenLine, Star, Trophy } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { CourseNotificationsDrawer } from '@/components/course/course-notifications-drawer';
import type {
    CourseHomeEarnedMedalItem,
    CourseHomeMedalsPreview,
    CourseHomeNotifications,
    CourseHomeProgress,
} from '@/lib/course-home-data';
import { cn } from '@/lib/utils';

const MEDAL_ICONS: Record<string, LucideIcon> = {
    first_story_written: PenLine,
    first_storyboard: BookOpen,
    first_animation: ImagePlay,
    first_approved_exercise: Star,
    ten_approved_exercises: Trophy,
    twenty_approved_exercises: Crown,
};

type CourseHomeHeaderProps = {
    progress: CourseHomeProgress;
    medals: CourseHomeMedalsPreview;
    notifications: CourseHomeNotifications;
};

function EarnedMedalTile({ medal }: { medal: CourseHomeEarnedMedalItem }) {
    const Icon = MEDAL_ICONS[medal.key] ?? Award;

    return (
        <li className="flex flex-col items-center gap-1.5 rounded-2xl bg-gold/10 px-2 py-3 ring-1 ring-gold/30">
            <span
                className="flex size-10 items-center justify-center rounded-full bg-gold/15 text-gold ring-1 ring-gold/25"
                aria-hidden
            >
                <Icon className="size-4" />
            </span>
            <span className="text-center text-[10px] font-bold leading-snug text-gold">
                {medal.title}
            </span>
            <span className="text-center text-[9px] font-medium leading-snug text-gold/75">
                {medal.earnedAtLabel}
            </span>
        </li>
    );
}

export function CourseHomeHeader({
    progress,
    medals,
    notifications,
}: CourseHomeHeaderProps) {
    const earnedCount = medals.earned.length;

    return (
        <header className="relative overflow-hidden rounded-[28px] bg-surface px-5 py-5 shadow-soft ring-1 ring-border">
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-gradient-to-bl from-purple-soft via-surface to-gold-soft/40"
            />

            <div className="relative flex flex-col gap-4">
                {/* Title row */}
                <div className="flex items-start justify-between gap-3">
                    <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span className="inline-flex w-fit items-center rounded-pill bg-purple-soft px-2.5 py-0.5 text-[11px] font-bold text-purple ring-1 ring-purple/15">
                            پنل هنرجو
                        </span>
                        <h1 className="font-display py-[7px] text-[1.15rem] leading-tight font-bold text-text">
                            باشگاه هنرجوی انیماتورشو
                        </h1>
                    </div>
                    <CourseNotificationsDrawer notifications={notifications} />
                </div>

                {/* XP progress */}
                <div className="flex flex-col gap-2 rounded-2xl bg-bg/80 px-3.5 py-3 ring-1 ring-border/60">
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-bold text-text">
                            سطح {progress.level}
                        </span>
                        <span className="text-[11px] font-medium text-muted">
                            {progress.currentLevelXp} از {progress.xpPerLevel} XP تا سطح بعدی
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

                {/* Medals */}
                <div className="flex flex-col gap-2">
                    <div className="flex items-center justify-between gap-2">
                        <p
                            className={cn(
                                'text-xs font-bold',
                                earnedCount > 0 ? 'text-gold' : 'text-muted',
                            )}
                        >
                            {earnedCount > 0
                                ? `${earnedCount} مدال دریافت شده`
                                : 'هیچ مدالی دریافت نکردید'}
                        </p>
                        <Link
                            href="/course/medals"
                            className="shrink-0 text-xs font-bold text-purple transition-opacity hover:opacity-75"
                        >
                            مشاهده همه مدال‌ها
                        </Link>
                    </div>

                    {earnedCount > 0 && (
                        <ul className="grid grid-cols-3 gap-2">
                            {medals.earned.map((medal) => (
                                <EarnedMedalTile key={medal.key} medal={medal} />
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </header>
    );
}
