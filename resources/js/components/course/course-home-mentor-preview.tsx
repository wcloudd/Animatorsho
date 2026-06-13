import { MessageCircle } from 'lucide-react';
import { CourseHomeComingSoonButton } from '@/components/course/course-home-coming-soon-button';
import { CourseHomeSectionCard } from '@/components/course/course-home-section-card';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type {
    CourseHomeMentorSummary,
    CourseHomeSectionVisual,
} from '@/lib/course-home-data';

type CourseHomeMentorPreviewProps = {
    mentorSummary: CourseHomeMentorSummary;
    visual: CourseHomeSectionVisual;
};

export function CourseHomeMentorPreview({
    mentorSummary,
    visual,
}: CourseHomeMentorPreviewProps) {
    return (
        <CourseHomeSectionCard
            title="گفتگو با استاد"
            description="پیام‌های مربوط به یادگیری و تمرین — جدا از پشتیبانی سایت"
            visual={visual}
            placeholderIcon={MessageCircle}
        >
            <div className="flex flex-col gap-3">
                <div className="flex items-center gap-3 rounded-2xl bg-bg px-4 py-3 ring-1 ring-border/70">
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-purple-soft text-purple ring-1 ring-purple/15">
                        <MessageCircle className="size-4" />
                    </span>
                    <div className="flex min-w-0 flex-1 flex-col gap-1">
                        <p className="text-sm font-bold text-text">
                            {mentorSummary.hasThread
                                ? 'گفتگوی فعال با استاد'
                                : 'هنوز پیامی نداری'}
                        </p>
                        <div className="flex flex-wrap items-center gap-2">
                            <ProfileStatusBadge tone="neutral">
                                {mentorSummary.status ?? 'به‌زودی فعال می‌شود'}
                            </ProfileStatusBadge>
                        </div>
                    </div>
                </div>

                <CourseHomeComingSoonButton>
                    ارسال پیام به استاد
                </CourseHomeComingSoonButton>
            </div>
        </CourseHomeSectionCard>
    );
}
