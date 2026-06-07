import { Link } from '@inertiajs/react';
import support from '@/routes/support';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-gold-soft px-5 py-6 shadow-soft ring-1 ring-border';

type PurchasesDisabledNoticeProps = {
    message: string;
    className?: string;
};

export function PurchasesDisabledNotice({
    message,
    className,
}: PurchasesDisabledNoticeProps) {
    return (
        <article className={cn(cardClassName, className)}>
            <p className="text-center text-sm font-medium leading-relaxed text-text">
                {message}
            </p>
            <Link
                href={support.index()}
                className="flex h-11 w-full items-center justify-center rounded-pill bg-green text-sm font-bold text-white shadow-soft transition-opacity hover:opacity-95"
            >
                تماس با پشتیبانی
            </Link>
        </article>
    );
}
