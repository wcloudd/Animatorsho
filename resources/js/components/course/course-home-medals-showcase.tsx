import { Award } from 'lucide-react';
import { CourseHomeSectionCard } from '@/components/course/course-home-section-card';
import type {
    CourseHomeMedalsPreview,
    CourseHomeSectionVisual,
} from '@/lib/course-home-data';
import { cn } from '@/lib/utils';

type CourseHomeMedalsShowcaseProps = {
    medals: CourseHomeMedalsPreview;
    visual: CourseHomeSectionVisual;
};

export function CourseHomeMedalsShowcase({
    medals,
    visual,
}: CourseHomeMedalsShowcaseProps) {
    const earnedCount = medals.earned.length;

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
                    {medals.locked.map((medal) => (
                        <li
                            key={medal.slug}
                            className="flex flex-col items-center gap-1.5 rounded-2xl bg-bg px-2 py-3 ring-1 ring-border/60"
                        >
                            <span
                                className={cn(
                                    'flex size-10 items-center justify-center rounded-full bg-purple-soft/50 text-purple/40 ring-1 ring-purple/10',
                                )}
                                aria-hidden
                            >
                                <Award className="size-4" />
                            </span>
                            <span className="text-center text-[10px] font-bold leading-snug text-muted">
                                {medal.title}
                            </span>
                        </li>
                    ))}
                </ul>
            </div>
        </CourseHomeSectionCard>
    );
}
