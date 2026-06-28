import { useEffect, useRef, useState } from 'react';
import { X } from 'lucide-react';

type LoadState = 'loading' | 'buffering' | 'ready' | 'error';

type LandingVideoModalProps = {
    videoSrc: string;
    ariaLabel: string;
    onClose: () => void;
};

export function LandingVideoModal({ videoSrc, ariaLabel, onClose }: LandingVideoModalProps) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const closeRef = useRef(onClose);
    closeRef.current = onClose;

    const [loadState, setLoadState] = useState<LoadState>('loading');
    const [bufferedPct, setBufferedPct] = useState(0);

    // Scroll lock + Escape key
    useEffect(() => {
        document.body.style.overflow = 'hidden';
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                videoRef.current?.pause();
                closeRef.current();
            }
        };
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, []);

    // Track load/buffer state and buffered percentage
    useEffect(() => {
        const video = videoRef.current;
        if (!video) return;

        function calcBuffered() {
            if (!video) return;
            const { duration, buffered } = video;
            if (!isFinite(duration) || duration === 0 || buffered.length === 0) return;
            let maxEnd = 0;
            for (let i = 0; i < buffered.length; i++) {
                maxEnd = Math.max(maxEnd, buffered.end(i));
            }
            setBufferedPct(Math.min(100, Math.round((maxEnd / duration) * 100)));
        }

        const onWaiting  = () => setLoadState('buffering');
        const onPlaying  = () => { setLoadState('ready'); calcBuffered(); };
        const onCanPlay  = () => { setLoadState('ready'); calcBuffered(); };
        const onProgress = () => calcBuffered();
        const onError    = () => setLoadState('error');

        video.addEventListener('waiting',  onWaiting);
        video.addEventListener('playing',  onPlaying);
        video.addEventListener('canplay',  onCanPlay);
        video.addEventListener('progress', onProgress);
        video.addEventListener('error',    onError);

        // Already ready (e.g. cached)
        if (video.readyState >= 3) {
            setLoadState('ready');
            calcBuffered();
        }

        return () => {
            video.removeEventListener('waiting',  onWaiting);
            video.removeEventListener('playing',  onPlaying);
            video.removeEventListener('canplay',  onCanPlay);
            video.removeEventListener('progress', onProgress);
            video.removeEventListener('error',    onError);
        };
    }, []);

    function handleClose() {
        videoRef.current?.pause();
        closeRef.current();
    }

    const showSpinner = loadState === 'loading' || loadState === 'buffering';

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label={ariaLabel}
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
            onClick={handleClose}
        >
            <div
                className="relative w-full max-w-3xl"
                onClick={(e) => e.stopPropagation()}
            >
                <button
                    type="button"
                    onClick={handleClose}
                    aria-label="بستن پخش‌کننده ویدیو"
                    className="absolute -top-10 right-0 flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/25"
                >
                    <X className="size-5" aria-hidden />
                </button>

                {/* relative needed so overlay children are positioned inside this box */}
                <div className="relative aspect-video w-full overflow-hidden rounded-[20px] bg-black">
                    <video
                        ref={videoRef}
                        src={videoSrc}
                        className="h-full w-full object-contain"
                        controls
                        autoPlay
                        playsInline
                        preload="metadata"
                    />

                    {/* Loading / buffering overlay — pointer-events-none so native controls remain usable */}
                    {showSpinner && (
                        <div
                            aria-live="polite"
                            className="pointer-events-none absolute inset-0 flex items-center justify-center"
                        >
                            <div className="flex flex-col items-center gap-2 rounded-xl bg-black/65 px-5 py-4">
                                <div className="h-7 w-7 animate-spin rounded-full border-[3px] border-white/30 border-t-white" />
                                <span className="text-sm font-medium text-white">
                                    {loadState === 'buffering'
                                        ? 'در حال بافر...'
                                        : 'در حال آماده‌سازی ویدئو...'}
                                </span>
                                {bufferedPct > 0 && (
                                    <span className="text-xs text-white/60">{bufferedPct}٪</span>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Error overlay */}
                    {loadState === 'error' && (
                        <div className="absolute inset-0 flex flex-col items-center justify-center gap-3 rounded-[20px] bg-black/85">
                            <span className="text-sm font-medium text-white">خطا در بارگذاری ویدئو</span>
                            <button
                                type="button"
                                onClick={handleClose}
                                className="rounded-pill bg-white/10 px-4 py-2 text-sm text-white transition-colors hover:bg-white/25"
                            >
                                بستن
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
