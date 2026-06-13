import { Link } from '@inertiajs/react';
import type { AdminFinanceSummary } from '@/types/admin';
import { cn } from '@/lib/utils';

type FinanceCardTone = 'neutral' | 'success' | 'warning';

function FinanceCard({
    label,
    value,
    subtitle,
    href,
    tone = 'neutral',
}: {
    label: string;
    value: string;
    subtitle?: string | null;
    href?: string;
    tone?: FinanceCardTone;
}) {
    const toneStyles = {
        neutral: 'bg-surface-warm ring-purple/10',
        success: 'bg-green-soft ring-green/25',
        warning: 'bg-gold-soft ring-gold/30',
    } as const;

    const valueStyles = {
        neutral: 'text-purple',
        success: 'text-green',
        warning: 'text-gold',
    } as const;

    const labelStyles = {
        neutral: 'text-muted',
        success: 'text-green',
        warning: 'text-gold',
    } as const;

    const content = (
        <>
            <span
                className={cn(
                    'text-xs font-medium leading-relaxed',
                    labelStyles[tone],
                )}
            >
                {label}
            </span>
            <span
                className={cn(
                    'font-liana text-lg leading-tight',
                    valueStyles[tone],
                )}
            >
                {value}
            </span>
            {subtitle ? (
                <span className="text-[10px] leading-relaxed text-muted">
                    {subtitle}
                </span>
            ) : null}
        </>
    );

    const className = cn(
        'flex min-h-[5.25rem] flex-col justify-between gap-1 rounded-2xl px-3.5 py-3 shadow-soft ring-1',
        toneStyles[tone],
        href && 'transition hover:ring-purple/25',
    );

    if (href) {
        return (
            <Link href={href} className={className}>
                {content}
            </Link>
        );
    }

    return <div className={className}>{content}</div>;
}

export function AdminFinanceSummaryPanel({
    financeSummary,
}: {
    financeSummary: AdminFinanceSummary;
}) {
    const {
        confirmedRevenueTotalFormatted,
        confirmedRevenueTodayFormatted,
        confirmedRevenueCurrentMonthFormatted,
        successfulPaymentsCount,
        pendingPaymentsCount,
        reviewingCardToCardCount,
        reviewingCardToCardAmountFormatted,
        reviewingInstallmentCount,
        reviewingInstallmentAmountFormatted,
        paidByMethod,
        topPackages,
        externalGrantsCount,
        externalGrantsAmountFormatted,
        activeLicensesCount,
    } = financeSummary;

    return (
        <div className="flex flex-col gap-3">
            <div className="grid grid-cols-2 gap-2.5">
                <FinanceCard
                    label="درآمد قطعی"
                    value={confirmedRevenueTotalFormatted}
                    tone="success"
                    href="/admin/payments"
                />
                <FinanceCard
                    label="فروش امروز"
                    value={confirmedRevenueTodayFormatted}
                    tone="success"
                    href="/admin/payments"
                />
                <FinanceCard
                    label="فروش این ماه"
                    value={confirmedRevenueCurrentMonthFormatted}
                    tone="success"
                    href="/admin/payments"
                />
                <FinanceCard
                    label="پرداخت موفق"
                    value={successfulPaymentsCount.toLocaleString('fa-IR')}
                    subtitle="تعداد پرداخت تأییدشده"
                    href="/admin/payments"
                />
                <FinanceCard
                    label="در انتظار بررسی"
                    value={pendingPaymentsCount.toLocaleString('fa-IR')}
                    subtitle="شامل در حال بررسی و معلق"
                    href="/admin/payments?status=reviewing"
                    tone={pendingPaymentsCount > 0 ? 'warning' : 'neutral'}
                />
                <FinanceCard
                    label="کارت‌به‌کارت در انتظار"
                    value={reviewingCardToCardCount.toLocaleString('fa-IR')}
                    subtitle={reviewingCardToCardAmountFormatted}
                    href="/admin/payments?status=reviewing"
                    tone={
                        reviewingCardToCardCount > 0 ? 'warning' : 'neutral'
                    }
                />
                <FinanceCard
                    label="اقساط در انتظار"
                    value={reviewingInstallmentCount.toLocaleString('fa-IR')}
                    subtitle={reviewingInstallmentAmountFormatted}
                    href="/admin/installments?status=awaiting_review"
                    tone={
                        reviewingInstallmentCount > 0 ? 'warning' : 'neutral'
                    }
                />
                <FinanceCard
                    label="لایسنس فعال"
                    value={activeLicensesCount.toLocaleString('fa-IR')}
                    subtitle="جدا از تعداد ثبت‌نام"
                    href="/admin/licenses"
                />
            </div>

            {paidByMethod.length > 0 ? (
                <div className="rounded-xl bg-purple-soft/40 px-3 py-2.5 ring-1 ring-purple/10">
                    <p className="mb-2 text-[11px] font-semibold text-purple">
                        فروش بر اساس روش پرداخت
                    </p>
                    <div className="flex flex-col gap-1.5">
                        {paidByMethod.map((row) => (
                            <div
                                key={row.method}
                                className="flex items-center justify-between gap-2 text-xs"
                            >
                                <span className="text-text">{row.label}</span>
                                <span className="shrink-0 text-muted">
                                    {row.count.toLocaleString('fa-IR')} ·{' '}
                                    {row.amountFormatted}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            ) : null}

            {externalGrantsCount > 0 ? (
                <div className="rounded-xl bg-surface-warm px-3 py-2.5 text-xs ring-1 ring-purple/10">
                    <span className="font-medium text-text">
                        دسترسی خارجی / دستی:{' '}
                    </span>
                    <span className="text-muted">
                        {externalGrantsCount.toLocaleString('fa-IR')} پرداخت ·{' '}
                        {externalGrantsAmountFormatted}
                    </span>
                </div>
            ) : null}

            {topPackages.length > 0 ? (
                <div className="rounded-xl bg-purple-soft/40 px-3 py-2.5 ring-1 ring-purple/10">
                    <p className="mb-2 text-[11px] font-semibold text-purple">
                        پرفروش‌ترین بسته‌ها
                    </p>
                    <div className="flex flex-col gap-1.5">
                        {topPackages.map((pkg) => (
                            <div
                                key={pkg.packageId}
                                className="flex items-center justify-between gap-2 text-xs"
                            >
                                <span className="min-w-0 truncate text-text">
                                    {pkg.title}
                                </span>
                                <span className="shrink-0 text-muted">
                                    {pkg.paidCount.toLocaleString('fa-IR')} ·{' '}
                                    {pkg.revenueFormatted}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            ) : null}
        </div>
    );
}
