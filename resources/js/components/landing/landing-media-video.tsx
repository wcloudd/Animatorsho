import { useEffect, useRef, useState } from 'react';
import { LandingMediaPlaceholder } from '@/components/landing/landing-media-placeholder';
import { useInViewport } from '@/hooks/use-in-viewport';
import { cn } from '@/lib/utils';

const SHELL_CLASS = 'w-full overflow-hidden rounded-[20px] bg-surface';
const MEDIA_CLASS = 'block h-full w-full border-0 object-cover outline-none';

type LandingMediaVideoProps = {
    videoSrc: string;
    posterSrc: string;
    ariaLabel?: string;
    className?: string;
    aspectClassName?: string;
    /** Overrides the default media element class (object-cover + h-full). Use for natural image sizing. */
    mediaClassName?: string;
    /** When false, video src is never attached (e.g. teacher photo, inactive chapter tab). */
    enabled?: boolean;
    placeholderVariant?: 'default' | 'video';
    placeholderMessage?: string;
};

export function LandingMediaVideo({
    videoSrc,
    posterSrc,
    ariaLabel = 'ویدیو معرفی',
    className,
    aspectClassName = 'aspect-[4/5]',
    mediaClassName,
    enabled = true,
    placeholderVariant = 'default',
    placeholderMessage,
}: LandingMediaVideoProps) {
    const mediaClass = mediaClassName ?? MEDIA_CLASS;
    const containerRef = useRef<HTMLDivElement>(null);
    const videoRef = useRef<HTMLVideoElement>(null);
    const isInViewport = useInViewport(containerRef, { enabled, rootMargin: '600px 0px' });

    // Sticky latch: once true, stays true while enabled.
    const [hasActivated, setHasActivated] = useState(false);
    // True after the video has buffered enough to display its first frame.
    const [hasFrame, setHasFrame] = useState(false);
    // True only if the video file itself returned a network/decode error.
    const [videoFailed, setVideoFailed] = useState(false);
    // True if the poster image URL returned a 404 or other error.
    // Tracked independently — a missing poster does NOT block video loading.
    const [posterFailed, setPosterFailed] = useState(false);

    // shouldAttachVideo deliberately does NOT depend on posterFailed.
    // A missing poster image is not a reason to withhold the video.
    const shouldAttachVideo = enabled && hasActivated && !videoFailed;

    // Activate when the element enters the 600 px pre-load zone.
    // Decoupled from poster/video load state.
    useEffect(() => {
        if (isInViewport && enabled && !hasActivated) {
            setHasActivated(true);
        }
    }, [isInViewport, enabled, hasActivated]);

    // Reset when disabled (e.g. switching chapter tab).
    useEffect(() => {
        if (!enabled) {
            setHasActivated(false);
            setHasFrame(false);
            setVideoFailed(false);
        }
    }, [enabled]);

    // Play when in viewport, pause when scrolled away.
    // Autoplay failure (play() rejected) is silently ignored —
    // the video remains visible on screen with its poster/first frame.
    useEffect(() => {
        const video = videoRef.current;
        if (!video || !shouldAttachVideo) return;
        if (isInViewport) {
            void video.play().catch(() => undefined);
        } else {
            video.pause();
        }
    }, [isInViewport, shouldAttachVideo]);

    useEffect(() => {
        return () => { videoRef.current?.pause(); };
    }, []);

    const shellClass = cn(SHELL_CLASS, aspectClassName, className);

    // ── Video active ────────────────────────────────────────────────────────
    // Source is attached. Show the video element immediately.
    // While the first frame hasn't loaded yet, an overlay covers any black
    // flash with the poster image (or the colored placeholder if poster is
    // also missing). Once canplay/loadeddata fires the overlay is removed.
    if (shouldAttachVideo) {
        return (
            <div ref={containerRef} className={cn(shellClass, 'relative')}>
                <video
                    ref={videoRef}
                    className={mediaClass}
                    autoPlay
                    muted
                    loop
                    playsInline
                    preload="metadata"
                    poster={posterSrc}
                    aria-label={ariaLabel}
                    onLoadedData={() => setHasFrame(true)}
                    onCanPlay={() => setHasFrame(true)}
                    onError={() => setVideoFailed(true)}
                >
                    <source src={videoSrc} type="video/mp4" />
                </video>

                {!hasFrame && (
                    <div className="pointer-events-none absolute inset-0">
                        {!posterFailed ? (
                            <img
                                src={posterSrc}
                                alt=""
                                className={mediaClass}
                                loading="eager"
                                decoding="async"
                                onError={() => setPosterFailed(true)}
                            />
                        ) : (
                            <LandingMediaPlaceholder
                                ariaLabel={ariaLabel}
                                className="h-full w-full"
                                variant={placeholderVariant}
                                message={placeholderMessage}
                            />
                        )}
                    </div>
                )}
            </div>
        );
    }

    // ── Not yet activated, or video file failed ──────────────────────────────
    // Show the poster image while waiting to enter the pre-load zone.
    // If the poster image itself is missing, show the colored placeholder.
    // Once the element enters the pre-load zone, shouldAttachVideo flips to
    // true and the branch above takes over regardless of posterFailed.
    return (
        <div ref={containerRef} className={shellClass}>
            {!posterFailed ? (
                <img
                    src={posterSrc}
                    alt=""
                    className={mediaClass}
                    loading="lazy"
                    decoding="async"
                    aria-label={ariaLabel}
                    onError={() => setPosterFailed(true)}
                />
            ) : (
                <LandingMediaPlaceholder
                    ariaLabel={ariaLabel}
                    className="h-full w-full"
                    variant={placeholderVariant}
                    message={placeholderMessage}
                />
            )}
        </div>
    );
}
