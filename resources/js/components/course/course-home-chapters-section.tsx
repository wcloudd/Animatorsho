import { ProfileSectionCard } from '@/components/profile/profile-section-card';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type { CourseHomeChapter } from '@/lib/course-home-data';
import { cn } from '@/lib/utils';

type CourseHomeChaptersSectionProps = {
    chapters: CourseHomeChapter[];
    hasFullAccess: boolean;
};

export function CourseHomeChaptersSection({
    chapters,
    hasFullAccess,
}: CourseHomeChaptersSectionProps) {
    return (
        <ProfileSectionCard
            title="سرفصل‌های دوره"
            description={
                hasFullAccess
                    ? 'همه فصل‌های دوره برایت فعال است. محتوای هر فصل به‌زودی از همین بخش در دسترس قرار می‌گیرد.'
                    : 'فصل‌هایی که برایشان دسترسی فعال داری اینجا نمایش داده می‌شوند.'
            }
        >
            <ul className="flex flex-col gap-3">
                {chapters.map((chapter) => (
                    <li
                        key={chapter.slug}
                        className={cn(
                            'flex flex-col gap-2 rounded-2xl px-4 py-3 ring-1',
                            chapter.isAccessible
                                ? 'bg-green-soft/40 ring-green/20'
                                : 'bg-bg ring-border/70',
                        )}
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div className="flex min-w-0 flex-1 flex-col gap-1">
                                {chapter.chapterNumber !== null ? (
                                    <span className="text-[11px] font-bold text-purple">
                                        فصل {chapter.chapterNumber}
                                    </span>
                                ) : null}
                                <p className="text-sm font-bold text-text">
                                    {chapter.title}
                                </p>
                            </div>
                            {chapter.accessLabel ? (
                                <ProfileStatusBadge tone="success">
                                    {chapter.accessLabel}
                                </ProfileStatusBadge>
                            ) : (
                                <ProfileStatusBadge tone="neutral">
                                    نیاز به خرید
                                </ProfileStatusBadge>
                            )}
                        </div>
                        <p className="text-xs font-medium leading-relaxed text-muted">
                            {chapter.isAccessible
                                ? 'محتوای این فصل به‌زودی از همین بخش در دسترس قرار می‌گیرد.'
                                : 'برای دسترسی به این فصل، ابتدا آن را خریداری کن.'}
                        </p>
                    </li>
                ))}
            </ul>
        </ProfileSectionCard>
    );
}
