import { useState } from 'react';
import { LandingMediaPlaceholder } from '@/components/landing/landing-media-placeholder';
import { cn } from '@/lib/utils';

type LandingMediaImageProps = {
    src: string;
    ariaLabel?: string;
    className?: string;
    imageClassName?: string;
    placeholderClassName?: string;
    placeholderVariant?: 'default' | 'video' | 'dark';
};

export function LandingMediaImage({
    src,
    ariaLabel,
    className,
    imageClassName,
    placeholderClassName,
    placeholderVariant = 'default',
}: LandingMediaImageProps) {
    const [imageFailed, setImageFailed] = useState(false);

    if (imageFailed) {
        if (placeholderVariant === 'dark') {
            return (
                <div
                    className={cn('bg-[#1c1a22]', className, placeholderClassName)}
                    aria-label={ariaLabel}
                    aria-hidden={ariaLabel ? undefined : true}
                />
            );
        }

        return (
            <LandingMediaPlaceholder
                ariaLabel={ariaLabel}
                className={cn(className, placeholderClassName)}
                variant={placeholderVariant === 'video' ? 'video' : 'default'}
            />
        );
    }

    return (
        <div className={className}>
            <img
                src={src}
                alt=""
                className={imageClassName}
                loading="lazy"
                decoding="async"
                aria-label={ariaLabel}
                onError={() => setImageFailed(true)}
            />
        </div>
    );
}
