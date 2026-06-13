import { BookOpen, ExternalLink, File, FileText, Image } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { showCoursePanelComingSoonToast } from '@/components/course/course-home-coming-soon-button';
import { CourseHomeSectionCard } from '@/components/course/course-home-section-card';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type {
    CourseHomeResourcePreview,
    CourseHomeSectionVisual,
} from '@/lib/course-home-data';

type CourseHomeResourcesPreviewProps = {
    resources: CourseHomeResourcePreview[];
    visual: CourseHomeSectionVisual;
};

const resourceIconByType: Record<string, LucideIcon> = {
    pdf: FileText,
    file: File,
    image: Image,
    link: ExternalLink,
    project_file: File,
};

function ResourceIcon({ type }: { type: string }) {
    const Icon = resourceIconByType[type] ?? File;

    return <Icon className="size-4" />;
}

export function CourseHomeResourcesPreview({
    resources,
    visual,
}: CourseHomeResourcesPreviewProps) {
    return (
        <CourseHomeSectionCard
            title="کتابخانه تمرین"
            description="فایل‌ها و رفرنس‌های تمرین"
            visual={visual}
            placeholderIcon={BookOpen}
        >
            <ul className="flex flex-col gap-2.5">
                {resources.map((resource) => (
                    <li key={resource.id}>
                        <button
                            type="button"
                            onClick={showCoursePanelComingSoonToast}
                            className="flex w-full items-center gap-3 rounded-2xl bg-bg px-4 py-3 text-start ring-1 ring-border/70 transition-colors hover:bg-purple-soft/30"
                        >
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-surface text-purple ring-1 ring-purple/10">
                                <ResourceIcon type={resource.type} />
                            </span>
                            <span className="flex min-w-0 flex-1 flex-col gap-1">
                                <span className="flex flex-wrap items-center gap-2">
                                    <span className="text-sm font-bold text-text">
                                        {resource.title}
                                    </span>
                                    <ProfileStatusBadge tone="neutral">
                                        {resource.typeLabel}
                                    </ProfileStatusBadge>
                                </span>
                                <span className="text-xs font-medium leading-relaxed text-muted">
                                    {resource.description}
                                </span>
                            </span>
                        </button>
                    </li>
                ))}
            </ul>
        </CourseHomeSectionCard>
    );
}
