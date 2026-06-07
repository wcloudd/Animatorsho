import { Link } from '@inertiajs/react';
import { profile } from '@/routes';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-gold-soft px-5 py-6 shadow-soft ring-1 ring-border';

type DuplicatePurchaseNoticeProps = {
    message: string;
    className?: string;
};

export function DuplicatePurchaseNotice({
    message,
    className,
}: DuplicatePurchaseNoticeProps) {
    return (
        <article className={cn(cardClassName, className)}>
            <p className="text-center text-sm font-medium leading-relaxed text-text">
                {message}
            </p>
            <p className="text-center text-xs font-medium leading-relaxed text-muted">
                در پروفایل می‌توانید پرداخت را ادامه دهید یا سفارش را لغو
                کنید.
            </p>
            <Link
                href={profile()}
                className="flex h-11 w-full items-center justify-center rounded-pill bg-green text-sm font-bold text-white shadow-soft transition-opacity hover:opacity-95"
            >
                مشاهده وضعیت در پروفایل
            </Link>
        </article>
    );
}
