import { Megaphone } from 'lucide-react';
import { useState } from 'react';
import { CourseHomeSectionCard } from '@/components/course/course-home-section-card';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type {
    CourseHomeSectionVisual,
    CourseHomeUpdatePreview,
    CourseHomeUpdateVisualTheme,
} from '@/lib/course-home-data';
import { cn } from '@/lib/utils';

type CourseHomeUpdatesPreviewProps = {
    updates: CourseHomeUpdatePreview[];
    visual: CourseHomeSectionVisual;
};

const updateThemeClassNames: Record<CourseHomeUpdateVisualTheme, string> = {
    default: 'bg-bg ring-border/70',
    purple: 'bg-purple-soft/45 ring-purple/25',
    gold: 'bg-gold-soft ring-gold/30',
    yellow: 'bg-gold-soft/90 ring-gold/40',
    blue: 'bg-blue/10 ring-blue/25',
    green: 'bg-green-soft ring-green/25',
    rainbow: 'course-update-theme-rainbow bg-surface ring-0',
};

function CourseHomeUpdateCard({ update }: { update: CourseHomeUpdatePreview }) {
    const [expanded, setExpanded] = useState(false);
    const hasBody = Boolean(update.body?.trim());

    return (
        <li
            className={cn(
                'flex flex-col gap-2 rounded-2xl px-4 py-3 ring-1',
                updateThemeClassNames[update.visualTheme],
            )}
        >
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex flex-wrap items-center gap-1.5">
                    <ProfileStatusBadge tone="neutral">
                        {update.typeLabel}
                    </ProfileStatusBadge>
                    {update.isPinned ? (
                        <ProfileStatusBadge tone="warning">
                            سنجاق‌شده
                        </ProfileStatusBadge>
                    ) : null}
                </div>
                <span className="text-[11px] font-medium text-muted">
                    {update.publishedAtLabel}
                </span>
            </div>
            <p
                className={cn(
                    'text-sm font-bold text-text',
                    update.visualTheme === 'rainbow' &&
                        'course-update-rainbow-title',
                )}
            >
                {update.title}
            </p>
            {update.summary ? (
                <p className="text-xs font-medium leading-relaxed text-muted">
                    {update.summary}
                </p>
            ) : null}
            {hasBody && expanded ? (
                <p className="whitespace-pre-wrap break-words text-xs font-medium leading-relaxed text-text">
                    {update.body}
                </p>
            ) : null}
            {hasBody ? (
                <button
                    type="button"
                    onClick={() => setExpanded((current) => !current)}
                    className="self-start rounded-pill px-3 py-1.5 text-xs font-bold text-purple ring-1 ring-purple/25 transition-opacity hover:opacity-90"
                >
                    {expanded ? 'بستن' : 'مشاهده متن کامل'}
                </button>
            ) : null}
        </li>
    );
}

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
            {updates.length === 0 ? (
                <p className="rounded-2xl bg-bg px-4 py-3 text-sm font-medium leading-relaxed text-muted ring-1 ring-border/70">
                    هنوز آپدیتی منتشر نشده است. به‌زودی خبرهای دوره اینجا
                    نمایش داده می‌شود.
                </p>
            ) : (
                <ul className="flex flex-col gap-2.5">
                    {updates.map((update) => (
                        <CourseHomeUpdateCard
                            key={update.id}
                            update={update}
                        />
                    ))}
                </ul>
            )}
        </CourseHomeSectionCard>
    );
}
