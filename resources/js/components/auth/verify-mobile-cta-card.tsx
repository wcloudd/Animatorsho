import { Link } from '@inertiajs/react';
import { VERIFY_MOBILE_CTA } from '@/lib/auth-form-data';
import { create as profileMobileCreate } from '@/routes/profile/mobile';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-gold-soft px-5 py-6 shadow-soft ring-1 ring-border';

type VerifyMobileCtaCardProps = {
    redirectQuery: { redirect: string };
    testId?: string;
};

export function VerifyMobileCtaCard({
    redirectQuery,
    testId = 'verify-mobile-cta',
}: VerifyMobileCtaCardProps) {
    const copy = VERIFY_MOBILE_CTA;

    return (
        <div className={cardClassName} data-test={testId}>
            <p className="text-center text-sm font-medium leading-relaxed text-text">
                {copy.message}
            </p>
            <Link
                href={profileMobileCreate({ query: redirectQuery })}
                className={cn(
                    'flex h-11 w-full items-center justify-center rounded-pill bg-green text-sm font-bold text-white shadow-soft transition-opacity hover:opacity-95',
                )}
            >
                {copy.ctaLabel}
            </Link>
        </div>
    );
}
