import { Link } from '@inertiajs/react';
import {
    CHECKOUT_FULL_URL,
    CHECKOUT_PURCHASE_RULES_URL,
} from '@/lib/checkout-urls';
import support from '@/routes/support';
import { cn } from '@/lib/utils';

const FOOTER_TITLE = 'انیماتورشو' as const;

const FOOTER_TRUST_CARD = {
    href: '#',
    title: 'نماد الکترونیکی',
    hint: 'برای مشاهده کلیک کنید',
} as const;

type FooterLink =
    | { type: 'anchor'; label: string; href: string }
    | { type: 'external'; label: string; href: string }
    | { type: 'inertia'; label: string; href: ReturnType<typeof support.index> };

type FooterLinkConfig =
    | { type: 'anchor'; label: string; href: string }
    | { type: 'external'; label: string; href: string }
    | { type: 'inertia'; label: string };

const FOOTER_LINKS: readonly FooterLinkConfig[] = [
    {
        type: 'anchor',
        label: 'دوره جامع انیماتورشو',
        href: CHECKOUT_FULL_URL,
    },
    {
        type: 'external',
        label: 'کانال انیماتورشو در ایتا',
        href: 'https://eitaa.com/nimvajabee',
    },
    {
        type: 'inertia',
        label: 'گروه پرسش و پاسخ',
    },
    {
        type: 'anchor',
        label: 'قوانین خرید دوره و سایت',
        href: CHECKOUT_PURCHASE_RULES_URL,
    },
] as const;

function getFooterLinks(): FooterLink[] {
    return FOOTER_LINKS.map((link) =>
        link.type === 'inertia'
            ? { ...link, href: support.index() }
            : link,
    );
}

const footerLinkClassName =
    'text-sm font-medium leading-relaxed text-white transition-opacity hover:opacity-80';

function EnamadLogo({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 32 32"
            className={cn('h-8 w-8 shrink-0 text-white', className)}
            aria-hidden
        >
            <path
                fill="currentColor"
                d="M22.5 8H12.8c-3.4 0-5.3 1.8-5.3 4.6 0 2 1 3.4 2.6 4.1l-3.8 6.8h3.9l2.9-5.2h4.1v5.2h3.3V8zm-3.9 7.4h-4.1c-1.5 0-2.3-.7-2.3-1.8s.8-1.8 2.3-1.8h4.1v3.6z"
            />
        </svg>
    );
}

function FooterNavLink({ link }: { link: FooterLink }) {
    if (link.type === 'external') {
        return (
            <a
                href={link.href}
                target="_blank"
                rel="noopener noreferrer"
                className={footerLinkClassName}
            >
                {link.label}
            </a>
        );
    }

    if (link.type === 'inertia') {
        return (
            <Link href={link.href} className={footerLinkClassName}>
                {link.label}
            </Link>
        );
    }

    return (
        <a href={link.href} className={footerLinkClassName}>
            {link.label}
        </a>
    );
}

function FooterTrustCard() {
    return (
        <a
            href={FOOTER_TRUST_CARD.href}
            className="flex w-[7.75rem] shrink-0 flex-col items-center justify-center gap-2.5 self-center rounded-2xl border border-white/75 px-3 py-5 text-center transition-opacity hover:opacity-90"
            aria-label={`${FOOTER_TRUST_CARD.title} — ${FOOTER_TRUST_CARD.hint}`}
        >
            <EnamadLogo />
            <span className="text-xs leading-tight font-bold">
                {FOOTER_TRUST_CARD.title}
            </span>
            <span className="text-[10px] leading-tight font-medium text-white/85">
                {FOOTER_TRUST_CARD.hint}
            </span>
        </a>
    );
}

export function LandingFooter() {
    const links = getFooterLinks();

    return (
        <footer
            className="w-full bg-black pb-32 text-white"
            aria-labelledby="landing-footer-title"
        >
            <div className="mx-auto flex w-full max-w-[390px] flex-col gap-8 px-4 py-10 min-[360px]:flex-row min-[360px]:items-stretch min-[360px]:gap-5">
                <div className="flex min-w-0 flex-1 flex-col items-start gap-5 text-right">
                    <h2
                        id="landing-footer-title"
                        className="font-display text-[1.75rem] leading-tight font-bold"
                    >
                        {FOOTER_TITLE}
                    </h2>

                    <nav aria-label="لینک‌های پاورقی">
                        <ul className="flex flex-col gap-3">
                            {links.map((link) => (
                                <li key={link.label}>
                                    <FooterNavLink link={link} />
                                </li>
                            ))}
                        </ul>
                    </nav>
                </div>

                <div
                    className="hidden min-[360px]:block w-px shrink-0 self-center bg-white/35"
                    style={{ minHeight: '8.5rem' }}
                    aria-hidden
                />

                <FooterTrustCard />
            </div>
        </footer>
    );
}
