import type { LucideIcon } from 'lucide-react';
import { Sparkles } from 'lucide-react';
import { cn } from '@/lib/utils';

type CourseHomeCompactImageStripProps = {
    imageUrl: string | null;
    imageAlt: string;
    variant?: 'banner' | 'section' | 'item';
    showPlaceholder?: boolean;
    placeholderTitle?: string;
    placeholderDescription?: string | null;
    placeholderIcon?: LucideIcon;
    className?: string;
};

const variantClassNames: Record<
    NonNullable<CourseHomeCompactImageStripProps['variant']>,
    string
> = {
    banner: 'aspect-[16/7] max-h-[4.75rem] rounded-t-[27px]',
    section: 'aspect-[16/8] max-h-[4rem] rounded-t-[27px]',
    item: 'aspect-[16/8] max-h-[3.5rem] rounded-xl',
};

export function CourseHomeCompactImageStrip({
    imageUrl,
    imageAlt,
    variant = 'banner',
    showPlaceholder = false,
    placeholderTitle,
    placeholderDescription = null,
    placeholderIcon: PlaceholderIcon = Sparkles,
    className,
}: CourseHomeCompactImageStripProps) {
    const hasImage =
        typeof imageUrl === 'string' && imageUrl.trim().length > 0;

    if (hasImage) {
        return (
            <div
                className={cn(
                    'relative w-full overflow-hidden bg-purple-soft/40',
                    variantClassNames[variant],
                    className,
                )}
            >
                <img
                    src={imageUrl}
                    alt={imageAlt}
                    className="size-full object-cover object-center"
                />
            </div>
        );
    }

    if (showPlaceholder && typeof placeholderTitle === 'string') {
        return (
            <div
                role="img"
                aria-label={imageAlt}
                className={cn(
                    'relative w-full overflow-hidden bg-gradient-to-l from-purple-soft via-gold-soft/60 to-bg',
                    variantClassNames[variant],
                    className,
                )}
            >
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_15%_50%,rgba(235,162,57,0.18),transparent_55%)]" />
                <div className="absolute inset-0 flex items-center justify-between px-4">
                    <div className="flex min-w-0 flex-col gap-0.5">
                        <span className="truncate font-display text-sm font-bold text-purple/85">
                            {placeholderTitle}
                        </span>
                        {placeholderDescription ? (
                            <span className="truncate text-[10px] font-medium text-muted">
                                {placeholderDescription}
                            </span>
                        ) : null}
                    </div>
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-surface/85 text-gold ring-1 ring-gold/20">
                        <PlaceholderIcon className="size-3.5" />
                    </span>
                </div>
            </div>
        );
    }

    if (variant === 'banner') {
        return (
            <div
                role="img"
                aria-label={imageAlt}
                className={cn(
                    'relative w-full overflow-hidden bg-gradient-to-l from-purple-soft via-gold-soft/70 to-purple-soft/50',
                    variantClassNames[variant],
                    className,
                )}
            >
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_80%_50%,rgba(96,55,168,0.12),transparent_55%)]" />
                <div className="absolute inset-0 flex items-center justify-between px-4">
                    <div className="flex flex-col gap-0.5">
                        <span className="font-display text-sm font-bold text-purple/80">
                            انیماتورشو
                        </span>
                        <span className="text-[10px] font-medium text-muted">
                            مسیر یادگیری و تمرین
                        </span>
                    </div>
                    <span className="flex size-8 items-center justify-center rounded-full bg-surface/80 text-purple ring-1 ring-purple/10">
                        <Sparkles className="size-3.5" />
                    </span>
                </div>
            </div>
        );
    }

    return null;
}
