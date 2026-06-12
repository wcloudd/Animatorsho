import { ProfileSectionCard } from '@/components/profile/profile-section-card';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';

type CourseHomePlaceholderSectionProps = {
    title: string;
    description: string;
};

export function CourseHomePlaceholderSection({
    title,
    description,
}: CourseHomePlaceholderSectionProps) {
    return (
        <ProfileSectionCard title={title} description={description}>
            <div className="flex items-center justify-between gap-3 rounded-2xl bg-bg px-4 py-3 ring-1 ring-border/70">
                <p className="text-sm font-medium text-muted">
                    این بخش به‌زودی اضافه می‌شود.
                </p>
                <ProfileStatusBadge tone="neutral">به‌زودی</ProfileStatusBadge>
            </div>
        </ProfileSectionCard>
    );
}
