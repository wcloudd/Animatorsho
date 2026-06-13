import { ClipboardList } from 'lucide-react';
import { CourseHomeComingSoonButton } from '@/components/course/course-home-coming-soon-button';
import { CourseHomeSectionCard } from '@/components/course/course-home-section-card';
import type {
    CourseHomeExercisesSummary,
    CourseHomeSectionVisual,
} from '@/lib/course-home-data';

type CourseHomeExercisesPreviewProps = {
    exercisesSummary: CourseHomeExercisesSummary;
    visual: CourseHomeSectionVisual;
};

export function CourseHomeExercisesPreview({
    exercisesSummary,
    visual,
}: CourseHomeExercisesPreviewProps) {
    const hasExercises = exercisesSummary.total > 0;

    return (
        <CourseHomeSectionCard
            title="تمرین‌های من"
            description="ارسال تمرین و دریافت بازخورد استاد"
            visual={visual}
            placeholderIcon={ClipboardList}
        >
            <div className="flex flex-col gap-3">
                <div className="flex items-center gap-3 rounded-2xl bg-bg px-4 py-3 ring-1 ring-border/70">
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-gold-soft text-gold ring-1 ring-gold/20">
                        <ClipboardList className="size-4" />
                    </span>
                    <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                        <p className="text-sm font-bold text-text">
                            {hasExercises
                                ? `${exercisesSummary.total} تمرین ارسال‌شده`
                                : 'هنوز تمرینی ارسال نکردی'}
                        </p>
                        <p className="text-xs font-medium text-muted">
                            {hasExercises
                                ? `${exercisesSummary.pending} تمرین در انتظار بررسی`
                                : 'بعد از تماشای جلسات، اولین تمرین را بفرست'}
                        </p>
                    </div>
                </div>

                <CourseHomeComingSoonButton variant="primary">
                    ارسال تمرین جدید
                </CourseHomeComingSoonButton>
            </div>
        </CourseHomeSectionCard>
    );
}
