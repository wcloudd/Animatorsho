import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    AlertTriangle,
    CreditCard,
    KeyRound,
    MessageCircle,
    Smartphone,
    Wallet,
} from 'lucide-react';
import type { AdminDashboardSummaryCard } from '@/types/admin';
import { cn } from '@/lib/utils';

const toneStyles = {
    warning: 'bg-gold-soft ring-gold/30 text-gold',
    danger: 'bg-red/10 ring-red/25 text-red/80',
    neutral: 'bg-surface-warm ring-purple/10',
} as const;

const countToneStyles = {
    warning: 'text-gold',
    danger: 'text-red',
    neutral: 'text-purple',
} as const;

const labelToneStyles = {
    warning: 'text-gold',
    danger: 'text-red/80',
    neutral: 'text-muted',
} as const;

const summaryIcons: Record<string, LucideIcon> = {
    pending_card_to_card: CreditCard,
    pending_installment: Wallet,
    pending_licenses: KeyRound,
    license_api_failures: AlertTriangle,
    open_support_tickets: MessageCircle,
    sms_issues: Smartphone,
};

type AdminDashboardSummaryCardProps = {
    card: AdminDashboardSummaryCard;
};

export function AdminDashboardSummaryCardLink({
    card,
}: AdminDashboardSummaryCardProps) {
    const Icon = summaryIcons[card.key];

    return (
        <Link
            href={card.href}
            className={cn(
                'flex min-h-[5.75rem] flex-col justify-between rounded-2xl px-3.5 py-3 shadow-soft ring-1 transition hover:ring-purple/25',
                toneStyles[card.tone],
            )}
        >
            <div className="flex items-start justify-between gap-2">
                <span
                    className={cn(
                        'text-xs font-medium leading-relaxed',
                        labelToneStyles[card.tone],
                    )}
                >
                    {card.label}
                </span>
                {Icon ? (
                    <Icon
                        className={cn(
                            'size-4 shrink-0 opacity-70',
                            labelToneStyles[card.tone],
                        )}
                        strokeWidth={1.75}
                        aria-hidden
                    />
                ) : null}
            </div>
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
