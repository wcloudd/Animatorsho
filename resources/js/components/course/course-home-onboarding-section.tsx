import { FileText, Play } from 'lucide-react';
import { useRef, useState } from 'react';
import { CourseHomeCompactImageStrip } from '@/components/course/course-home-compact-image-strip';
import { showCoursePanelComingSoonToast } from '@/components/course/course-home-coming-soon-button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { CourseHomeOnboarding } from '@/lib/course-home-data';
import { cn } from '@/lib/utils';

type CourseHomeOnboardingSectionProps = {
    onboarding: CourseHomeOnboarding;
};

function hasConfiguredUrl(url: string | null | undefined): url is string {
    return typeof url === 'string' && url.trim().length > 0;
}

function isSameOriginPublicPath(url: string): boolean {
    return url.startsWith('/');
}

export function CourseHomeOnboardingSection({
    onboarding,
}: CourseHomeOnboardingSectionProps) {
    const [videoOpen, setVideoOpen] = useState(false);
    const videoRef = useRef<HTMLVideoElement>(null);

    const hasVideo = hasConfiguredUrl(onboarding.videoUrl);
    const hasPdf = hasConfiguredUrl(onboarding.pdfUrl);
    const videoTitle =
        onboarding.videoTitle?.trim() || onboarding.videoGuideLabel;

    const handleVideoOpenChange = (open: boolean) => {
        setVideoOpen(open);

        if (!open) {
            const video = videoRef.current;
            video?.pause();

            try {
                if (video) {
                    video.currentTime = 0;
                }
            } catch {
                // Ignore seek errors while metadata is loading.
            }
        }
    };

    const handleVideoClick = () => {
        if (hasVideo) {
            setVideoOpen(true);

            return;
        }

        showCoursePanelComingSoonToast();
    };

    const pdfButtonClassName = cn(
        'flex h-10 items-center justify-center gap-1.5 rounded-pill px-3 text-sm font-bold transition-opacity hover:opacity-95',
        'bg-surface text-purple ring-1 ring-purple/25 hover:bg-purple-soft/60',
    );

    return (
        <>
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

                        {hasPdf ? (
                            <a
                                href={onboarding.pdfUrl}
                                download={
                                    isSameOriginPublicPath(onboarding.pdfUrl) &&
                                    hasConfiguredUrl(onboarding.pdfDownloadName)
                                        ? onboarding.pdfDownloadName
                                        : undefined
                                }
                                target="_blank"
                                rel="noreferrer"
                                className={pdfButtonClassName}
                            >
                                <FileText className="size-3.5" />
                                {onboarding.pdfGuideLabel}
                            </a>
                        ) : (
                            <button
                                type="button"
                                onClick={showCoursePanelComingSoonToast}
                                className={pdfButtonClassName}
                            >
                                <FileText className="size-3.5" />
                                {onboarding.pdfGuideLabel}
                            </button>
                        )}
                    </div>
                </div>
            </article>

            {hasVideo ? (
                <Dialog open={videoOpen} onOpenChange={handleVideoOpenChange}>
                    <DialogContent className="max-w-[calc(100%-1.5rem)] gap-4 rounded-[24px] border-border bg-surface p-4 sm:max-w-md">
                        <DialogHeader className="text-start">
                            <DialogTitle className="text-base font-bold text-text">
                                {videoTitle}
                            </DialogTitle>
                            <DialogDescription className="text-sm font-medium text-muted">
                                راهنمای شروع کار با پنل هنرجو
                            </DialogDescription>
                        </DialogHeader>

                        <div className="overflow-hidden rounded-2xl bg-bg ring-1 ring-border/70">
                            <video
                                ref={videoRef}
                                key={onboarding.videoUrl}
                                className="max-h-[min(70dvh,28rem)] w-full object-contain"
                                controls
                                playsInline
                                preload="metadata"
                                poster={
                                    hasConfiguredUrl(onboarding.videoPosterUrl)
                                        ? onboarding.videoPosterUrl
                                        : undefined
                                }
                            >
                                <source
                                    src={onboarding.videoUrl}
                                    type="video/mp4"
                                />
                            </video>
                        </div>
                    </DialogContent>
                </Dialog>
            ) : null}
        </>
    );
}
