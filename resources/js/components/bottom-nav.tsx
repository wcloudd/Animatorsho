import { Link, usePage } from '@inertiajs/react';
import { Headphones, User } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useState, type TransitionEvent } from 'react';
import { useScrollDirectionNavVisible } from '@/hooks/use-scroll-direction';
import { home, profile } from '@/routes';
import support from '@/routes/support';
import { cn, toUrl } from '@/lib/utils';

const centerSlotClass = 'w-20';
const pillMinClass = 'min-h-[4.25rem]';
const centerCardClass = 'h-[5.75rem] w-20';

const LOGO_INACTIVE_SRC = '/images/animatorsho-logo.svg?v=2' as const;
const LOGO_ACTIVE_SRC = '/images/animatorsho-logo-active.svg?v=2' as const;

const centerNavLinkClassName = cn(
    'pointer-events-auto flex flex-col items-center justify-end gap-0.5 rounded-[1.125rem] border-2 border-nav px-1 pb-2 pt-2',
    centerCardClass,
    'transition-shadow duration-150',
);

function preloadNavLogos(): void {
    for (const src of [LOGO_INACTIVE_SRC, LOGO_ACTIVE_SRC]) {
        const image = new Image();
        image.src = src;
    }
}

type CenterNavLogoProps = {
    active: boolean;
};

function CenterNavLogo({ active }: CenterNavLogoProps) {
    return (
        <div
            className="relative h-[60px] w-7 shrink-0"
            aria-hidden
        >
            <img
                src={LOGO_INACTIVE_SRC}
                alt=""
                width={28}
                height={60}
                decoding="sync"
                fetchPriority="high"
                className={cn(
                    'absolute inset-0 size-full object-contain',
                    active && 'invisible',
                )}
            />
            <img
                src={LOGO_ACTIVE_SRC}
                alt=""
                width={28}
                height={60}
                decoding="sync"
                fetchPriority="high"
                className={cn(
                    'absolute inset-0 size-full object-contain',
                    !active && 'invisible',
                )}
            />
        </div>
    );
}

function isActive(currentUrl: string, href: string): boolean {
    const path = href.split('?')[0];

    if (path === '/') {
        return currentUrl === '/' || currentUrl === '';
    }

    return currentUrl === path || currentUrl.startsWith(`${path}/`);
}

type SideNavItemProps = {
    label: string;
    href: ReturnType<typeof home>;
    icon: LucideIcon;
    active: boolean;
};

function SideNavItem({ label, href, icon: Icon, active }: SideNavItemProps) {
    return (
        <Link
            href={href}
            className={cn(
                'flex min-w-0 flex-1 flex-col items-center justify-center gap-1 py-3 transition-colors',
                active && 'relative',
            )}
            aria-current={active ? 'page' : undefined}
        >
            <Icon
                className={cn(
                    'size-6 stroke-[1.5]',
                    active ? 'text-purple' : 'text-nav-inactive',
                )}
                aria-hidden
            />
            <span
                className={cn(
                    'text-[11px] font-medium leading-none',
                    active ? 'text-purple' : 'text-nav-inactive',
                )}
            >
                {label}
            </span>
            {active && (
                <span
                    className="absolute bottom-1.5 left-1/2 h-1 w-7 -translate-x-1/2 rounded-full bg-purple"
                    aria-hidden
                />
            )}
        </Link>
    );
}

type CenterNavItemProps = {
    active: boolean;
};

function CenterNavItem({ active }: CenterNavItemProps) {
    return (
        <div
            className={cn(
                'pointer-events-none absolute bottom-0 left-1/2 z-10 flex -translate-x-1/2 flex-col items-center',
                centerSlotClass,
            )}
        >
            <Link
                href={home()}
                className={cn(
                    centerNavLinkClassName,
                    active
                        ? 'bg-purple-gradient shadow-elevated'
                        : 'bg-surface shadow-elevated',
                )}
                aria-current={active ? 'page' : undefined}
            >
                <CenterNavLogo active={active} />
                <span
                    className={cn(
                        'text-center text-[11px] leading-tight',
                        active
                            ? 'font-semibold text-gold'
                            : 'font-medium text-nav-inactive',
                    )}
                >
                    انیماتورشو
                </span>
            </Link>
            {active && (
                <span
                    className="pointer-events-none mt-1 h-1 w-7 shrink-0 rounded-full bg-purple"
                    aria-hidden
                />
            )}
        </div>
    );
}

const NAV_SLIDE_MS = 420;

export function BottomNav() {
    useEffect(() => {
        preloadNavLogos();
    }, []);

    const { url } = usePage();
    const navVisible = useScrollDirectionNavVisible();
    const [navInteractive, setNavInteractive] = useState(true);
    const homeHref = toUrl(home());
    const supportHref = toUrl(support.index());
    const profileHref = toUrl(profile());

    const homeActive = isActive(url, homeHref);
    const supportActive = isActive(url, supportHref);
    const profileActive = isActive(url, profileHref);

    useEffect(() => {
        if (navVisible) {
            setNavInteractive(true);
            return;
        }

        const timer = window.setTimeout(
            () => setNavInteractive(false),
            NAV_SLIDE_MS,
        );

        return () => window.clearTimeout(timer);
    }, [navVisible]);

    const handleTransitionEnd = (event: TransitionEvent<HTMLElement>): void => {
        if (event.propertyName === 'transform' && !navVisible) {
            setNavInteractive(false);
        }
    };

    return (
        <nav
            className={cn(
                'fixed inset-x-0 bottom-0 z-50 pb-[env(safe-area-inset-bottom)]',
                'will-change-transform transform-gpu motion-reduce:transition-none',
                'transition-transform duration-[420ms] ease-[cubic-bezier(0.32,0.72,0,1)]',
                navVisible ? 'translate-y-0' : 'translate-y-[calc(100%+0.75rem)]',
                !navInteractive && 'pointer-events-none',
            )}
            aria-label="ناوبری اصلی"
            aria-hidden={navInteractive ? undefined : true}
            inert={navInteractive ? undefined : true}
            onTransitionEnd={handleTransitionEnd}
        >
            <div className="mx-auto w-full max-w-[390px] px-4 pb-3">
                <div className="relative pt-6">
                    <div
                        className={cn(
                            'relative flex items-center justify-between overflow-visible',
                            pillMinClass,
                            'rounded-pill border-2 border-nav bg-nav-bar px-2 shadow-soft',
                        )}
                    >
                        <SideNavItem
                            label="پشتیبانی"
                            href={support.index()}
                            icon={Headphones}
                            active={supportActive}
                        />
                        <div className={cn('shrink-0', centerSlotClass)} aria-hidden />
                        <SideNavItem
                            label="پروفایل"
                            href={profile()}
                            icon={User}
                            active={profileActive}
                        />
                        <CenterNavItem active={homeActive} />
                    </div>
                </div>
            </div>
        </nav>
    );
}
