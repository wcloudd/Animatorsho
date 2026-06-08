import { LandingMediaImage } from '@/components/landing/landing-media-image';
import { LANDING_PURCHASE_KEY_IMAGE } from '@/lib/landing-media';
import { cn } from '@/lib/utils';

export function PurchaseSectionIllustration() {
    return (
        <LandingMediaImage
            src={LANDING_PURCHASE_KEY_IMAGE.src}
            ariaLabel={LANDING_PURCHASE_KEY_IMAGE.ariaLabel}
            className="flex w-full justify-center"
            imageClassName="mx-auto h-auto max-h-[200px] w-auto max-w-[220px] object-contain"
            placeholderClassName={cn(
                'mx-auto flex h-[180px] w-[200px] items-center justify-center rounded-[32px]',
                'bg-gradient-to-br from-purple-soft via-surface to-gold-soft',
            )}
            placeholderVariant="default"
        />
    );
}
