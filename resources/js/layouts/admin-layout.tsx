import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

const navItems = [
    { href: '/admin', label: 'داشبورد', match: '/admin', exact: true },
    { href: '/admin/packages', label: 'بسته‌ها', match: '/admin/packages' },
    { href: '/admin/orders', label: 'سفارش‌ها', match: '/admin/orders' },
    { href: '/admin/payments', label: 'پرداخت‌ها', match: '/admin/payments' },
    { href: '/admin/licenses', label: 'لایسنس‌ها', match: '/admin/licenses' },
    { href: '/admin/support', label: 'پشتیبانی', match: '/admin/support' },
    { href: '/admin/sms', label: 'پیامک', match: '/admin/sms' },
] as const satisfies ReadonlyArray<{
    href: string;
    label: string;
    match: string;
    exact?: boolean;
}>;

export default function AdminLayout({ children }: { children: ReactNode }) {
    const { url } = usePage();

    return (
        <div className="min-h-dvh overflow-x-hidden bg-bg text-text" dir="rtl">
            <header className="sticky top-0 z-10 border-b border-purple/10 bg-surface">
                <div className="mx-auto flex w-full max-w-[390px] flex-col gap-3 px-4 py-4 sm:max-w-5xl">
                    <div className="flex items-center justify-between gap-3">
                        <h1 className="min-w-0 truncate font-liana text-lg text-purple">
                            پنل مدیریت انیماتورشو
                        </h1>
                        <Link
                            href="/"
                            className="text-sm text-muted transition hover:text-purple"
                        >
                            بازگشت به سایت
                        </Link>
                    </div>
                    <nav className="flex flex-wrap gap-1.5">
                        {navItems.map((item) => {
                            const isActive = item.exact
                                ? url === item.match ||
                                  url === `${item.match}/`
                                : url.startsWith(item.match);

                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={cn(
                                        'rounded-pill px-2.5 py-1 text-xs font-medium transition',
                                        isActive
                                            ? 'bg-purple text-white'
                                            : 'bg-purple-soft text-purple hover:bg-purple/10',
                                    )}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
                </div>
            </header>
            <main className="mx-auto w-full max-w-[390px] px-4 py-6 sm:max-w-5xl">
                {children}
            </main>
        </div>
    );
}
