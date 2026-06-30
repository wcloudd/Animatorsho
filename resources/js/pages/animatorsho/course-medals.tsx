import { Head, Link } from '@inertiajs/react';
import { Award, BookOpen, ChevronLeft, Crown, ImagePlay, Lock, PenLine, Star, Trophy } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { PageContainer } from '@/components/page-container';
import type {
    CourseHomeEarnedMedalItem,
    CourseHomeMedalItem,
    CourseHomeMedalsPreview,
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

type CourseMedalsPageProps = {
    medals: CourseHomeMedalsPreview;
};

function EarnedMedalCard({ medal }: { medal: CourseHomeEarnedMedalItem }) {
    const Icon = MEDAL_ICONS[medal.key] ?? Award;

    return (
        <li className="flex flex-col items-center gap-2 rounded-2xl bg-gold-soft p-4 text-center ring-1 ring-gold/30">
            <span
                className="flex size-12 items-center justify-center rounded-full bg-gold/15 text-gold ring-1 ring-gold/30"
                aria-hidden
            >
                <Icon className="size-5" />
            </span>
            <span className="text-sm font-bold leading-snug text-gold">
                {medal.title}
            </span>
            <span className="text-[11px] font-medium text-gold/70">
                {medal.earnedAtLabel}
            </span>
        </li>
    );
}

function LockedMedalCard({ medal }: { medal: CourseHomeMedalItem }) {
    const Icon = MEDAL_ICONS[medal.key] ?? Award;

    return (
        <li className="relative flex flex-col items-center gap-2 rounded-2xl bg-surface p-4 text-center opacity-60 ring-1 ring-border/60">
            <span
                className="flex size-12 items-center justify-center rounded-full bg-purple-soft/40 text-muted ring-1 ring-border/40"
                aria-hidden
            >
                <Icon className="size-5" />
            </span>
            <span className="text-sm font-bold leading-snug text-muted">
                {medal.title}
            </span>
            <span className="inline-flex items-center gap-1 text-[11px] font-medium text-muted/70">
                <Lock className="size-3" aria-hidden />
                دریافت نشده
            </span>
        </li>
    );
}

export default function CourseMedals({ medals }: CourseMedalsPageProps) {
    const { earned, locked, totalAvailable } = medals;
    const earnedCount = earned.length;

    return (
        <>
            <Head title="همه مدال‌ها" />
            <PageContainer>
                <div className="flex flex-col gap-5">
                    <Link
                        href="/course"
                        className="inline-flex items-center gap-1 self-start text-xs font-bold text-purple"
                    >
                        <ChevronLeft className="size-4" />
                        بازگشت به پنل هنرجو
                    </Link>

                    <header className="flex flex-col items-center gap-2 text-center">
                        <span className="inline-flex items-center rounded-pill bg-gold-soft px-3 py-1 text-[11px] font-bold text-gold ring-1 ring-gold/20">
                            باشگاه هنرجوی انیماتورشو
                        </span>
                        <h1 className="font-display text-xl font-bold text-text">
                            همه مدال‌ها
                        </h1>
                        <p className="text-sm font-medium text-muted">
                            {earnedCount > 0
                                ? `${earnedCount} از ${totalAvailable} مدال کسب شده`
                                : `هنوز مدالی کسب نکردی — ${totalAvailable} مدال منتظرته`}
                        </p>
                    </header>

                    {earned.length > 0 && (
                        <section aria-labelledby="earned-medals-heading">
                            <h2
                                id="earned-medals-heading"
                                className="mb-3 text-sm font-bold text-text"
                            >
                                مدال‌های کسب شده
                                <span className="mr-1.5 text-xs font-medium text-gold">
                                    ({earnedCount})
                                </span>
                            </h2>
                            <ul className="grid grid-cols-2 gap-3">
                                {earned.map((medal) => (
                                    <EarnedMedalCard key={medal.key} medal={medal} />
                                ))}
                            </ul>
                        </section>
                    )}

                    {locked.length > 0 && (
                        <section aria-labelledby="locked-medals-heading">
                            <h2
                                id="locked-medals-heading"
                                className={cn(
                                    'mb-3 text-sm font-bold text-text',
                                    earned.length > 0 && 'mt-2',
                                )}
                            >
                                مدال‌های در دسترس
                                <span className="mr-1.5 text-xs font-medium text-muted">
                                    ({locked.length})
                                </span>
                            </h2>
                            <ul className="grid grid-cols-2 gap-3">
                                {locked.map((medal) => (
                                    <LockedMedalCard key={medal.key} medal={medal} />
                                ))}
                            </ul>
                        </section>
                    )}
                </div>
            </PageContainer>
        </>
    );
}
