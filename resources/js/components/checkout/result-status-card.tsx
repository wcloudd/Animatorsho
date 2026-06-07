import type { CheckoutResultContent } from '@/lib/checkout-result-data';
import { RESULT_VISUAL_STYLES } from '@/lib/checkout-result-data';
import { cn } from '@/lib/utils';

type ResultStatusCardProps = {
    content: CheckoutResultContent;
};

export function ResultStatusCard({ content }: ResultStatusCardProps) {
    const visual = RESULT_VISUAL_STYLES[content.visualTone];
    const Icon = content.icon;

    return (
        <article
            className={cn(
                'flex w-full flex-col items-center gap-4 rounded-[28px] px-5 py-6 text-center shadow-soft ring-1 ring-border',
                visual.cardClassName,
            )}
        >
            <div
                className={cn(
                    'flex size-16 items-center justify-center rounded-full shadow-soft',
                    visual.iconWrapClassName,
                )}
            >
                <Icon className="size-8" strokeWidth={2.25} aria-hidden />
            </div>

            <span
                className={cn(
                    'inline-flex rounded-pill px-3 py-1 text-xs font-bold ring-1',
                    visual.badgeClassName,
                )}
            >
                {content.statusBadgeLabel}
            </span>
        </article>
    );
}
