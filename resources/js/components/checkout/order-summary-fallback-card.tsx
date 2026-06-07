import { Link } from '@inertiajs/react';
import { checkout } from '@/routes';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col items-center gap-4 rounded-[28px] bg-surface px-5 py-6 text-center shadow-soft ring-1 ring-border';

export function OrderSummaryFallbackCard() {
    return (
        <article className={cardClassName}>
            <h2 className="text-base font-bold text-text">
                انتخاب ثبت‌نام مشخص نیست
            </h2>
            <p className="text-sm font-medium leading-relaxed text-muted">
                برای ادامه، ابتدا یکی از گزینه‌های ثبت‌نام را در صفحه قبل
                انتخاب کن.
            </p>
            <Link
                href={checkout()}
                className={cn(
                    'btn-cta-green flex h-12 w-full max-w-[280px] items-center justify-center rounded-pill px-4 text-sm font-bold text-white',
                )}
            >
                بازگشت به انتخاب ثبت‌نام
            </Link>
        </article>
    );
}
