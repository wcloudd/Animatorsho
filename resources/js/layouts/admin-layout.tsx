import { Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    CalendarClock,
    CreditCard,
    Headphones,
    KeyRound,
    LayoutDashboard,
    Menu,
    MessageSquare,
    Package,
    Settings,
    Shield,
    ShoppingBag,
    Smartphone,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { AdminTextLink } from '@/components/admin/admin-text-link';
import { NoIndexSeoHead } from '@/components/seo/seo-head';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';

const navItems = [
    {
        href: '/admin',
        label: 'داشبورد',
        match: '/admin',
        exact: true,
        icon: LayoutDashboard,
    },
    {
        href: '/admin/packages',
        label: 'بسته‌ها',
        match: '/admin/packages',
        icon: Package,
    },
    {
        href: '/admin/orders',
        label: 'سفارش‌ها',
        match: '/admin/orders',
        icon: ShoppingBag,
    },
    {
        href: '/admin/payments',
        label: 'پرداخت‌ها',
        match: '/admin/payments',
        icon: CreditCard,
    },
    {
        href: '/admin/installments',
        label: 'پیگیری اقساط',
        match: '/admin/installments',
        icon: CalendarClock,
    },
    {
        href: '/admin/licenses',
        label: 'لایسنس‌ها',
        match: '/admin/licenses',
        icon: KeyRound,
    },
    {
        href: '/admin/consultations',
        label: 'مشاوره‌ها',
        match: '/admin/consultations',
        icon: MessageSquare,
    },
    {
        href: '/admin/support',
        label: 'پشتیبانی',
        match: '/admin/support',
        icon: Headphones,
    },
    {
        href: '/admin/sms',
        label: 'پیامک',
        match: '/admin/sms',
        icon: Smartphone,
    },
    {
        href: '/admin/security-events',
        label: 'امنیت',
        match: '/admin/security-events',
        icon: Shield,
    },
    {
        href: '/admin/site-settings',
        label: 'تنظیمات سایت',
        match: '/admin/site-settings',
        icon: Settings,
    },
] as const satisfies ReadonlyArray<{
    href: string;
    label: string;
    match: string;
    exact?: boolean;
    icon: LucideIcon;
}>;

type AdminNavItem = (typeof navItems)[number];

function isNavItemActive(url: string, item: AdminNavItem): boolean {
    return item.exact
        ? url === item.match || url === `${item.match}/`
        : url.startsWith(item.match);
}

function AdminNavLink({
    item,
    url,
    onNavigate,
    className,
}: {
    item: AdminNavItem;
    url: string;
    onNavigate?: () => void;
    className?: string;
}) {
    const isActive = isNavItemActive(url, item);
    const Icon = item.icon;

    return (
        <Link
            href={item.href}
            onClick={onNavigate}
            aria-current={isActive ? 'page' : undefined}
            className={cn(
                'flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                isActive
                    ? 'bg-purple text-white shadow-xs ring-1 ring-purple/20'
                    : 'text-text hover:bg-purple-soft hover:text-purple',
                className,
            )}
        >
            <Icon className="size-4 shrink-0" aria-hidden />
            <span>{item.label}</span>
        </Link>
    );
}

export default function AdminLayout({ children }: { children: ReactNode }) {
    const { url } = usePage();
    const [menuOpen, setMenuOpen] = useState(false);

    return (
        <div className="min-h-dvh overflow-x-hidden bg-bg text-text" dir="rtl">
            <NoIndexSeoHead />
            <header className="sticky top-0 z-10 border-b border-purple/10 bg-surface/95 shadow-soft backdrop-blur-sm">
                <div className="mx-auto flex w-full max-w-[390px] flex-col gap-3 px-4 py-4 sm:max-w-5xl">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex min-w-0 items-center gap-2">
                            <Sheet open={menuOpen} onOpenChange={setMenuOpen}>
                                <SheetTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        className="shrink-0 border-purple/15 bg-surface text-purple hover:bg-purple-soft lg:hidden"
                                        aria-label="باز کردن منوی مدیریت"
                                    >
                                        <Menu className="size-5" />
                                    </Button>
                                </SheetTrigger>
                                <SheetContent
                                    side="right"
                                    className="w-[min(100vw-2rem,20rem)] border-purple/10 bg-surface p-0 text-text"
                                >
                                    <SheetHeader className="border-b border-purple/10 px-4 py-4 text-right">
                                        <SheetTitle className="font-display text-base text-purple">
                                            منوی مدیریت
                                        </SheetTitle>
                                    </SheetHeader>
                                    <nav className="flex flex-col gap-1 p-3">
                                        {navItems.map((item) => (
                                            <AdminNavLink
                                                key={item.href}
                                                item={item}
                                                url={url}
                                                onNavigate={() =>
                                                    setMenuOpen(false)
                                                }
                                            />
                                        ))}
                                    </nav>
                                </SheetContent>
                            </Sheet>
                            <h1 className="min-w-0 truncate font-display text-lg text-purple">
                                پنل مدیریت
                            </h1>
                        </div>
                        <AdminTextLink href="/" variant="subtle">
                            بازگشت به سایت
                        </AdminTextLink>
                    </div>
                    <nav
                        aria-label="منوی مدیریت"
                        className="hidden lg:grid lg:grid-cols-5 lg:gap-1.5"
                    >
                        {navItems.map((item) => (
                            <AdminNavLink
                                key={item.href}
                                item={item}
                                url={url}
                            />
                        ))}
                    </nav>
                </div>
            </header>
            <main className="mx-auto w-full max-w-[390px] px-4 py-6 sm:max-w-5xl">
                {children}
            </main>
        </div>
    );
}
