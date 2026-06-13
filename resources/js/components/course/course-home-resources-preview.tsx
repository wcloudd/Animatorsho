import { BookOpen } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { CourseHomeSectionCard } from '@/components/course/course-home-section-card';
import { CourseResourceRow } from '@/components/course/course-resource-row';
import type {
    CourseHomeResourcePreview,
    CourseHomeSectionVisual,
} from '@/lib/course-home-data';

type CourseHomeResourcesPreviewProps = {
    resources: CourseHomeResourcePreview[];
    resourcesIndexUrl: string;
    visual: CourseHomeSectionVisual;
};

export function CourseHomeResourcesPreview({
    resources,
    resourcesIndexUrl,
    visual,
}: CourseHomeResourcesPreviewProps) {
    return (
        <CourseHomeSectionCard
            title="کتابخانه تمرین"
            description="فایل‌ها و رفرنس‌های تمرین"
            visual={visual}
            placeholderIcon={BookOpen}
        >
            {resources.length === 0 ? (
                <p className="rounded-2xl bg-bg px-4 py-3 text-sm font-medium leading-relaxed text-muted ring-1 ring-border/70">
                    هنوز منبعی منتشر نشده است. فایل‌های تمرین و رفرنس‌ها به‌زودی
                    اینجا قرار می‌گیرند.
                </p>
            ) : (
                <div className="flex flex-col gap-3">
                    <ul className="flex flex-col gap-2.5">
                        {resources.map((resource) => (
                            <li key={resource.id}>
                                <CourseResourceRow resource={resource} />
                            </li>
                        ))}
                    </ul>
                    <Link
                        href={resourcesIndexUrl}
                        className="self-start rounded-pill px-4 py-2 text-xs font-bold text-purple ring-1 ring-purple/25 transition-colors hover:bg-purple-soft/40"
                    >
                        مشاهده همه منابع
                    </Link>
                </div>
            )}
        </CourseHomeSectionCard>
    );
}
