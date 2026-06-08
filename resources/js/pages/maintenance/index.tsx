import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { NoIndexSeoHead } from '@/components/seo/seo-head';
import { login, logout } from '@/routes';
import { cn } from '@/lib/utils';

type MaintenancePageProps = {
    title: string;
    message: string;
};

type MaintenanceSharedProps = {
    auth?: {
        user?: {
            id: number;
            name: string;
        } | null;
        isAdmin?: boolean;
    };
};

const actionButtonClassName = cn(
    'flex h-11 w-full max-w-[280px] items-center justify-center rounded-pill text-sm font-bold shadow-soft transition-opacity hover:opacity-95',
);

export default function MaintenanceIndex({
    title,
    message,
}: MaintenancePageProps) {
    const pageProps = usePage<MaintenanceSharedProps>().props;
    const user = pageProps.auth?.user ?? null;
    const isAuthenticated = user !== null;

    const pageTitle = title?.trim() !== '' ? title : 'در حال بروزرسانی هستیم';
    const pageMessage =
        message?.trim() !== ''
            ? message
            : 'در حال به‌روزرسانی سایت هستیم. لطفاً چند دقیقه دیگر دوباره سر بزنید.';

    return (
        <div dir="rtl" className="min-h-dvh bg-bg text-text">
            <Head title={pageTitle} />
            <NoIndexSeoHead />
            <main className="mx-auto flex min-h-dvh w-full max-w-[390px] flex-col items-center justify-center gap-5 px-4 py-12 text-center">
                <h1 className="font-display text-2xl font-bold text-purple">
                    {pageTitle}
                </h1>
                <p className="max-w-[320px] text-sm font-medium leading-relaxed text-muted">
                    {pageMessage}
                </p>

                {isAuthenticated ? (
                    <div className="flex w-full max-w-[320px] flex-col items-center gap-4">
                        <p
                            className="text-xs font-medium leading-relaxed text-muted"
                            data-test="maintenance-logged-in-copy"
                        >
                            شما با حساب کاربری وارد شده‌اید. برای ورود با حساب
                            ادمین، ابتدا از حساب فعلی خارج شوید.
                        </p>
                        <Link
                            href={logout()}
                            method="post"
                            as="button"
                            className={cn(
                                actionButtonClassName,
                                'bg-surface text-red ring-1 ring-border',
                            )}
                            data-test="maintenance-logout-button"
                        >
                            خروج از حساب
                        </Link>
                    </div>
                ) : (
                    <Link
                        href={login()}
                        className={cn(
                            actionButtonClassName,
                            'bg-green text-white',
                        )}
                        data-test="maintenance-admin-login-link"
                    >
                        ورود ادمین
                    </Link>
                )}
            </main>
        </div>
    );
}

MaintenanceIndex.layout = (page: ReactNode) => page;
