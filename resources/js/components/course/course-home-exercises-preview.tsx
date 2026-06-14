import { ClipboardList } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { CourseHomeSectionCard } from '@/components/course/course-home-section-card';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
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
    const latest = exercisesSummary.latest;

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
                            {latest ? (
                                <span className="inline-flex flex-wrap items-center gap-1.5">
                                    <span className="truncate">
                                        آخرین: {latest.title}
                                    </span>
                                    <ProfileStatusBadge tone={latest.statusTone}>
                                        {latest.statusLabel}
                                    </ProfileStatusBadge>
                                </span>
                            ) : hasExercises ? (
                                `${exercisesSummary.pending} تمرین در انتظار بررسی`
                            ) : (
                                'بعد از تماشای جلسات، اولین تمرین را بفرست'
                            )}
                        </p>
                    </div>
                </div>

                <Link
                    href={
                        hasExercises
                            ? exercisesSummary.exercisesIndexUrl
                            : exercisesSummary.createUrl
                    }
                    className="inline-flex items-center justify-center rounded-pill bg-purple px-4 py-2.5 text-xs font-bold text-white shadow-soft transition-colors hover:bg-purple/90"
                >
                    {hasExercises ? 'مشاهده تمرین‌ها' : 'ارسال تمرین'}
                </Link>
            </div>
        </CourseHomeSectionCard>
    );
}
