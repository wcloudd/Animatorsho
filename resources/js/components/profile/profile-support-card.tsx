import { Link } from '@inertiajs/react';
import support from '@/routes/support';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-green-soft px-5 py-6 shadow-soft ring-1 ring-border';

export function ProfileSupportCard() {
    return (
        <article className={cardClassName}>
            <header className="flex flex-col gap-1.5">
                <h2 className="text-base font-bold text-text">
                    نیاز به کمک داری؟
                </h2>
                <p className="text-sm font-medium leading-relaxed text-muted">
                    اگر درباره لایسنس، پرداخت یا دسترسی دوره سوالی داری، از
                    بخش پشتیبانی پیام بفرست.
                </p>
            </header>

            <Link
                href={support.index()}
                className={cn(
                    'flex h-11 w-full items-center justify-center rounded-pill bg-green text-sm font-bold text-white shadow-soft transition-opacity hover:opacity-95',
                )}
            >
                رفتن به پشتیبانی
            </Link>
        </article>
    );
}
