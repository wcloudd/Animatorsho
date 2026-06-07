import { Link } from '@inertiajs/react';
import type { AdminDashboardSummaryCard } from '@/types/admin';
import { cn } from '@/lib/utils';

const toneStyles = {
    warning: 'bg-gold-soft ring-gold/25 text-gold',
    danger: 'bg-red-soft/70 ring-red/25 text-red',
    neutral: 'bg-surface ring-border text-muted',
} as const;

const countToneStyles = {
    warning: 'text-gold',
    danger: 'text-red',
    neutral: 'text-text',
} as const;

type AdminDashboardSummaryCardProps = {
    card: AdminDashboardSummaryCard;
};

export function AdminDashboardSummaryCardLink({
    card,
}: AdminDashboardSummaryCardProps) {
    return (
        <Link
            href={card.href}
            className={cn(
                'flex min-h-[5.5rem] flex-col justify-between rounded-2xl px-4 py-3 shadow-soft ring-1 transition hover:opacity-95',
                toneStyles[card.tone],
            )}
        >
            <span className="text-xs font-medium leading-relaxed">
                {card.label}
            </span>
            <span
                className={cn(
                    'font-liana text-2xl leading-none',
                    countToneStyles[card.tone],
                )}
            >
                {card.count.toLocaleString('fa-IR')}
            </span>
        </Link>
    );
}
