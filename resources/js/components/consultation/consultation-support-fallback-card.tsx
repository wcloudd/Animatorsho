import { Link } from '@inertiajs/react';
import { CONSULTATION_SUPPORT_FALLBACK } from '@/lib/consultation-form-data';
import support from '@/routes/support';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-gold-soft px-5 py-6 shadow-soft ring-1 ring-border';

export function ConsultationSupportFallbackCard() {
    const fallback = CONSULTATION_SUPPORT_FALLBACK;

    return (
        <article className={cardClassName}>
            <header className="flex flex-col gap-1.5">
                <h2 className="text-base font-bold text-text">
                    {fallback.title}
                </h2>
                <p className="text-sm font-medium leading-relaxed text-muted">
                    {fallback.text}
                </p>
            </header>

            <Link
                href={support.index()}
                className={cn(
                    'flex h-11 w-full items-center justify-center rounded-pill bg-surface text-sm font-bold text-text shadow-soft ring-1 ring-border transition-colors hover:bg-purple-soft',
                )}
            >
                {fallback.ctaLabel}
            </Link>
        </article>
    );
}
