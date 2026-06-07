import { Link, usePage } from '@inertiajs/react';
import { login, register } from '@/routes';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-gold-soft px-5 py-6 shadow-soft ring-1 ring-border';

export function AccountNoticeCard() {
    const { url } = usePage();
    const redirectQuery = { redirect: url };

    return (
        <article className={cardClassName}>
            <p className="text-center text-sm font-medium leading-relaxed text-text">
                برای ادامه خرید، وارد حساب کاربری شو یا ثبت‌نام کن تا سفارش و
                لایسنس دوره داخل پروفایل شما ذخیره شود.
            </p>
            <div className="flex gap-3">
                <Link
                    href={login({ query: redirectQuery })}
                    className={cn(
                        'flex h-11 flex-1 items-center justify-center rounded-pill bg-surface text-sm font-bold text-text shadow-soft ring-1 ring-border transition-colors hover:bg-purple-soft',
                    )}
                >
                    ورود
                </Link>
                <Link
                    href={register({ query: redirectQuery })}
                    className={cn(
                        'flex h-11 flex-1 items-center justify-center rounded-pill bg-green text-sm font-bold text-white shadow-soft transition-opacity hover:opacity-95',
                    )}
                >
                    ثبت‌نام
                </Link>
            </div>
        </article>
    );
}
