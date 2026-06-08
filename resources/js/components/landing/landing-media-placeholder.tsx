import { cn } from '@/lib/utils';

type LandingMediaPlaceholderProps = {
    ariaLabel?: string;
    className?: string;
    variant?: 'default' | 'video';
    message?: string;
};

export function LandingMediaPlaceholder({
    ariaLabel,
    className,
    variant = 'default',
    message,
}: LandingMediaPlaceholderProps) {
    if (variant === 'video') {
        return (
            <div
                className={cn(
                    'flex flex-col items-center justify-center gap-2 bg-gradient-to-br from-purple-soft via-surface to-gold-soft',
                    className,
                )}
                aria-label={ariaLabel}
            >
                <div className="flex size-14 items-center justify-center rounded-full bg-surface shadow-soft ring-1 ring-border">
                    <span
                        className="ms-0.5 block size-0 border-y-[10px] border-y-transparent border-s-[16px] border-s-purple"
                        aria-hidden
                    />
                </div>
                {message ? (
                    <p className="text-xs font-medium text-muted">{message}</p>
                ) : null}
            </div>
        );
    }

    return (
        <div
            className={cn('bg-[#f0f7f9]', className)}
            aria-label={ariaLabel}
        />
    );
}
