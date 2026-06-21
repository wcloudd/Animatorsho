import { Award, BookOpen, Crown, ImagePlay, PenLine, Star, Trophy } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { CourseHomeSectionCard } from '@/components/course/course-home-section-card';
import type {
    CourseHomeEarnedMedalItem,
    CourseHomeMedalItem,
    CourseHomeMedalsPreview,
    CourseHomeSectionVisual,
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

type CourseHomeMedalsShowcaseProps = {
    medals: CourseHomeMedalsPreview;
    visual: CourseHomeSectionVisual;
};

function MedalTile({
    medal,
    earned,
}: {
    medal: CourseHomeMedalItem | CourseHomeEarnedMedalItem;
    earned: boolean;
}) {
    const Icon = MEDAL_ICONS[medal.key] ?? Award;
    const earnedAtLabel =
        earned && 'earnedAtLabel' in medal ? medal.earnedAtLabel : null;

    return (
        <li
            className={cn(
                'flex flex-col items-center gap-1.5 rounded-2xl px-2 py-3 ring-1',
                earned
                    ? 'bg-gold-soft ring-gold/30'
                    : 'bg-bg ring-border/60',
            )}
        >
            <span
                className={cn(
                    'flex size-10 items-center justify-center rounded-full ring-1',
                    earned
                        ? 'bg-gold/15 text-gold ring-gold/25'
                        : 'bg-purple-soft/50 text-purple/40 ring-purple/10',
                )}
                aria-hidden
            >
                <Icon className="size-4" />
            </span>
            <span
                className={cn(
                    'text-center text-[10px] font-bold leading-snug',
                    earned ? 'text-gold' : 'text-muted',
                )}
            >
                {medal.title}
            </span>
            {earnedAtLabel ? (
                <span className="text-center text-[9px] font-medium leading-snug text-gold/80">
                    {earnedAtLabel}
                </span>
            ) : null}
        </li>
    );
}

export function CourseHomeMedalsShowcase({
    medals,
    visual,
}: CourseHomeMedalsShowcaseProps) {
    const earnedCount = medals.earned.length;
    const earnedKeys = new Set(medals.earned.map((m) => m.key));

    const allMedals: Array<
        | { type: 'earned'; medal: CourseHomeEarnedMedalItem }
        | { type: 'locked'; medal: CourseHomeMedalItem }
    > = [
        ...medals.earned.map((m) => ({ type: 'earned' as const, medal: m })),
        ...medals.locked
            .filter((m) => !earnedKeys.has(m.key))
            .map((m) => ({ type: 'locked' as const, medal: m })),
    ];

    return (
        <CourseHomeSectionCard
            title="مدال‌ها"
            description="با ارسال و تایید تمرین، مدال می‌گیری"
            visual={visual}
            placeholderIcon={Award}
        >
            <div className="flex flex-col gap-3">
                <p className="text-xs font-medium text-muted">
                    {earnedCount > 0
                        ? `${earnedCount} از ${medals.totalAvailable} مدال کسب‌شده`
                        : `۰ از ${medals.totalAvailable} مدال — هنوز مدالی نگرفتی`}
                </p>

                <ul className="grid grid-cols-3 gap-2">
                    {allMedals.map(({ type, medal }) => (
                        <MedalTile
                            key={medal.key}
                            medal={medal}
                            earned={type === 'earned'}
                        />
                    ))}
                </ul>
            </div>
        </CourseHomeSectionCard>
    );
}
