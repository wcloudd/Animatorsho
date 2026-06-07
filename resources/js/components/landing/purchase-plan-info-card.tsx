import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type PurchasePlanInfoCardProps = {
    title: string;
    children: ReactNode;
    ctaLabel: string;
    ctaHref: string;
    ctaVariant: 'secondary' | 'outline';
};

const ctaVariantClassName = {
    secondary:
        'bg-green text-white shadow-soft hover:opacity-95',
    outline:
        'bg-surface text-text shadow-soft ring-1 ring-border hover:bg-purple-soft',
} as const;

export function PurchasePlanInfoCard({
    title,
    children,
    ctaLabel,
    ctaHref,
    ctaVariant,
}: PurchasePlanInfoCardProps) {
    return (
        <article className="flex w-full flex-col items-center gap-4 rounded-[28px] bg-surface px-5 py-6 text-center shadow-soft ring-1 ring-border">
            <h3 className="text-base font-bold text-text">{title}</h3>
            <div className="flex flex-col items-center gap-1.5 text-sm font-medium leading-relaxed text-[#646464]">
                {children}
            </div>
            <a
                href={ctaHref}
                className={cn(
                    'mt-1 flex h-11 w-full max-w-[280px] items-center justify-center rounded-pill px-4 text-sm font-bold transition-opacity',
                    ctaVariantClassName[ctaVariant],
                )}
            >
                {ctaLabel}
            </a>
        </article>
    );
}
