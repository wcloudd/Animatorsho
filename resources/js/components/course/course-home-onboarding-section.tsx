import { Play, FileText } from 'lucide-react';
import { toast } from 'sonner';
import { CourseHomeCompactImageStrip } from '@/components/course/course-home-compact-image-strip';
import { showCoursePanelComingSoonToast } from '@/components/course/course-home-coming-soon-button';
import type { CourseHomeOnboarding } from '@/lib/course-home-data';
import { cn } from '@/lib/utils';

type CourseHomeOnboardingSectionProps = {
    onboarding: CourseHomeOnboarding;
};

export function CourseHomeOnboardingSection({
    onboarding,
}: CourseHomeOnboardingSectionProps) {
    const handleVideoClick = () => {
        if (
            typeof onboarding.videoUrl === 'string' &&
            onboarding.videoUrl.trim() !== ''
        ) {
            window.open(onboarding.videoUrl, '_blank', 'noopener,noreferrer');

            return;
        }

        showCoursePanelComingSoonToast();
    };

    const handlePdfClick = () => {
        if (
            typeof onboarding.pdfUrl === 'string' &&
            onboarding.pdfUrl.trim() !== ''
        ) {
            window.open(onboarding.pdfUrl, '_blank', 'noopener,noreferrer');

            return;
        }

        toast.info('راهنمای PDF به‌زودی در دسترس قرار می‌گیرد.');
    };

    return (
        <article className="flex w-full flex-col overflow-hidden rounded-[28px] bg-surface shadow-soft ring-1 ring-border">
            <CourseHomeCompactImageStrip
                imageUrl={onboarding.imageUrl}
                imageAlt={onboarding.imageAlt}
                variant="banner"
            />

            <div className="flex flex-col gap-4 px-5 py-5">
                <header className="flex flex-col gap-1.5">
                    <h2 className="text-base font-bold text-text">
                        {onboarding.title} / {onboarding.heading}
                    </h2>
                    <p className="text-sm font-medium leading-relaxed text-muted">
                        {onboarding.description}
                    </p>
                </header>

                <div className="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        onClick={handleVideoClick}
                        className={cn(
                            'flex h-10 items-center justify-center gap-1.5 rounded-pill px-3 text-sm font-bold transition-opacity hover:opacity-95',
                            'btn-cta-purple text-white shadow-soft',
                        )}
                    >
                        <Play className="size-3.5 fill-current" />
                        {onboarding.videoGuideLabel}
                    </button>
                    <button
                        type="button"
                        onClick={handlePdfClick}
                        className={cn(
                            'flex h-10 items-center justify-center gap-1.5 rounded-pill px-3 text-sm font-bold transition-opacity hover:opacity-95',
                            'bg-surface text-purple ring-1 ring-purple/25 hover:bg-purple-soft/60',
                        )}
                    >
                        <FileText className="size-3.5" />
                        {onboarding.pdfGuideLabel}
                    </button>
                </div>
            </div>
        </article>
    );
}
