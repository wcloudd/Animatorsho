import { AUTH_SUPPORT_EITAA_URL, AUTH_SUPPORT_FALLBACK } from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col gap-3 rounded-[24px] bg-purple-soft/50 px-4 py-4 shadow-xs ring-1 ring-border/80';

type AuthSupportFallbackCardProps = {
    visible?: boolean;
};

export function AuthSupportFallbackCard({
    visible = true,
}: AuthSupportFallbackCardProps) {
    const fallback = AUTH_SUPPORT_FALLBACK;

    if (!visible) {
        return null;
    }

    return (
        <article className={cardClassName}>
            <header className="flex flex-col gap-1">
                <h2 className="text-sm font-bold text-text">{fallback.title}</h2>
                <p className="text-xs font-medium leading-relaxed text-muted">
                    {fallback.text}
                </p>
            </header>

            <a
                href={AUTH_SUPPORT_EITAA_URL}
                target="_blank"
                rel="noopener noreferrer"
                className={cn(
                    'flex h-10 w-full items-center justify-center rounded-pill bg-surface text-xs font-bold text-purple shadow-soft ring-1 ring-border transition-colors hover:bg-white',
                )}
            >
                {fallback.ctaLabel}
            </a>
        </article>
    );
}
