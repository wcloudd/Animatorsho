import { useEffect, useRef, useState } from 'react';
import { useInViewport } from '@/hooks/use-in-viewport';
import { cn } from '@/lib/utils';

const SHELL_CLASS =
    'w-full overflow-hidden rounded-[32px] bg-surface';

const PLACEHOLDER_FILL_CLASS = 'bg-[#f0f7f9]';

const MEDIA_CLASS = 'block h-full w-full border-0 object-cover outline-none';

type LandingMediaVideoProps = {
    videoSrc: string;
    posterSrc: string;
    ariaLabel?: string;
    className?: string;
    aspectClassName?: string;
    /** When false, video src is never attached (e.g. inactive chapter tab). */
    enabled?: boolean;
};

function LazyPosterImage({
    posterSrc,
    ariaLabel,
    className,
    onError,
}: {
    posterSrc: string;
    ariaLabel?: string;
    className?: string;
    onError: () => void;
}) {
    return (
        <img
            src={posterSrc}
            alt=""
            className={className}
            loading="lazy"
            decoding="async"
            aria-label={ariaLabel}
            onError={onError}
        />
    );
}

export function LandingMediaVideo({
    videoSrc,
    posterSrc,
    ariaLabel = 'ویدیو معرفی',
    className,
    aspectClassName = 'aspect-[4/5]',
    enabled = true,
}: LandingMediaVideoProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const videoRef = useRef<HTMLVideoElement>(null);
    const isInViewport = useInViewport(containerRef, { enabled });
    const [mediaState, setMediaState] = useState<
        'video' | 'poster' | 'placeholder'
    >('video');
    const [hasActivated, setHasActivated] = useState(false);

    const shouldAttachVideo =
        enabled && hasActivated && mediaState === 'video';

    useEffect(() => {
        if (isInViewport && enabled && mediaState === 'video') {
            setHasActivated(true);
        }
    }, [enabled, isInViewport, mediaState]);

    useEffect(() => {
        if (!enabled) {
            setHasActivated(false);
        }
    }, [enabled]);

    useEffect(() => {
        const video = videoRef.current;

        if (!video || !shouldAttachVideo) {
            return;
        }

        if (isInViewport && enabled) {
            void video.play().catch(() => undefined);
            return;
        }

        video.pause();

        try {
            video.currentTime = 0;
        } catch {
            // Ignore seek errors while metadata is loading.
        }
    }, [enabled, isInViewport, shouldAttachVideo]);

    useEffect(() => {
        return () => {
            videoRef.current?.pause();
        };
    }, []);

    const shellClass = cn(SHELL_CLASS, aspectClassName, className);

    if (mediaState === 'placeholder') {
        return (
            <div
                ref={containerRef}
                className={cn(shellClass, PLACEHOLDER_FILL_CLASS)}
                aria-label={ariaLabel}
            />
        );
    }

    if (mediaState === 'poster') {
        return (
            <div ref={containerRef} className={shellClass}>
                <LazyPosterImage
                    posterSrc={posterSrc}
                    className={MEDIA_CLASS}
                    onError={() => setMediaState('placeholder')}
                />
            </div>
        );
    }

    return (
        <div ref={containerRef} className={shellClass}>
            {shouldAttachVideo ? (
                <video
                    ref={videoRef}
                    className={MEDIA_CLASS}
                    autoPlay
                    muted
                    loop
                    playsInline
                    preload="none"
                    poster={posterSrc}
                    aria-label={ariaLabel}
                    onError={() => setMediaState('poster')}
                >
                    <source src={videoSrc} type="video/mp4" />
                </video>
            ) : (
                <LazyPosterImage
                    posterSrc={posterSrc}
                    ariaLabel={ariaLabel}
                    className={MEDIA_CLASS}
                    onError={() => setMediaState('placeholder')}
                />
            )}
        </div>
    );
}
