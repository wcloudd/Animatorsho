import { Megaphone } from 'lucide-react';
import { CourseHomeSectionCard } from '@/components/course/course-home-section-card';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type {
    CourseHomeSectionVisual,
    CourseHomeUpdatePreview,
} from '@/lib/course-home-data';

type CourseHomeUpdatesPreviewProps = {
    updates: CourseHomeUpdatePreview[];
    visual: CourseHomeSectionVisual;
};

export function CourseHomeUpdatesPreview({
    updates,
    visual,
}: CourseHomeUpdatesPreviewProps) {
    return (
        <CourseHomeSectionCard
            title="آخرین آپدیت‌ها"
            description="خبرهای مهم دوره و تمرین‌ها"
            visual={visual}
            placeholderIcon={Megaphone}
        >
            <ul className="flex flex-col gap-2.5">
                {updates.map((update) => (
                    <li
                        key={update.id}
                        className="flex flex-col gap-2 rounded-2xl bg-bg px-4 py-3 ring-1 ring-border/70"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <ProfileStatusBadge tone="neutral">
                                {update.typeLabel}
                            </ProfileStatusBadge>
                            <span className="text-[11px] font-medium text-muted">
                                {update.publishedAtLabel}
                            </span>
                        </div>
                        <p className="text-sm font-bold text-text">
                            {update.title}
                        </p>
                        <p className="text-xs font-medium leading-relaxed text-muted">
                            {update.summary}
                        </p>
                    </li>
                ))}
            </ul>
        </CourseHomeSectionCard>
    );
}
