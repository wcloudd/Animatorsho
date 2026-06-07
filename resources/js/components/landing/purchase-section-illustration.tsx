import { useState } from 'react';
import { cn } from '@/lib/utils';

const PURCHASE_KEY_IMAGE_SRC =
    '/media/landing/posters/purchase-section-key.webp' as const;

export function PurchaseSectionIllustration() {
    const [imageFailed, setImageFailed] = useState(false);

    if (imageFailed) {
        return (
            <div
                className={cn(
                    'mx-auto flex h-[180px] w-[200px] items-center justify-center rounded-[32px]',
                    'bg-gradient-to-br from-purple-soft via-surface to-gold-soft',
                )}
                aria-hidden
            />
        );
    }

    return (
        <div className="flex w-full justify-center">
            <img
                src={PURCHASE_KEY_IMAGE_SRC}
                alt=""
                className="mx-auto h-auto max-h-[200px] w-auto max-w-[220px] object-contain"
                loading="lazy"
                decoding="async"
                onError={() => setImageFailed(true)}
            />
        </div>
    );
}
