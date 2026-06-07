import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import type { ProfileOrderHistoryItem } from '@/lib/profile-data';
import { formatProfileDate } from '@/lib/format-profile-date';
import { formatTomanPrice } from '@/lib/format-toman';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';

type ProfileOrderHistorySectionProps = {
    orderHistory: ProfileOrderHistoryItem[];
    hasOrderHistory: boolean;
};

export function ProfileOrderHistorySection({
    orderHistory,
    hasOrderHistory,
}: ProfileOrderHistorySectionProps) {
    const [open, setOpen] = useState(false);

    if (!hasOrderHistory) {
        return null;
    }

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <section
                className="rounded-2xl bg-surface-warm px-4 py-3 ring-1 ring-border/70"
                aria-labelledby="profile-order-history-heading"
            >
                <CollapsibleTrigger
                    className="flex w-full items-center justify-between gap-3 text-right"
                    aria-expanded={open}
                >
                    <div className="flex flex-col gap-0.5">
                        <h2
                            id="profile-order-history-heading"
                            className="text-sm font-bold text-text"
                        >
                            سوابق سفارش‌ها
                        </h2>
                        <p className="text-xs font-medium text-muted">
                            {orderHistory.length} سفارش
                        </p>
                    </div>
                    <ChevronDown
                        className={cn(
                            'size-4 shrink-0 text-muted transition-transform duration-200',
                            open && 'rotate-180',
                        )}
                        aria-hidden
                    />
                </CollapsibleTrigger>

                <CollapsibleContent className="pt-3">
                    <ul className="flex flex-col divide-y divide-border/60">
                        {orderHistory.map((order) => {
                            const createdAtLabel = formatProfileDate(
                                order.createdAt,
                            );

                            return (
                                <li
                                    key={order.id}
                                    className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex flex-col gap-1">
                                            <span className="text-sm font-bold text-text">
                                                {order.title}
                                            </span>
                                            <span
                                                className="text-xs font-medium text-muted"
                                                dir="ltr"
                                            >
                                                {order.orderNumber}
                                            </span>
                                        </div>
                                        <ProfileStatusBadge
                                            tone={order.statusTone}
                                        >
                                            {order.status}
                                        </ProfileStatusBadge>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-medium text-muted">
                                        <span>{order.paymentType}</span>
                                        {order.paymentMethod ? (
                                            <span>{order.paymentMethod}</span>
                                        ) : null}
                                        <span>
                                            {formatTomanPrice(
                                                order.amountToman,
                                            )}
                                        </span>
                                        {createdAtLabel ? (
                                            <span>{createdAtLabel}</span>
                                        ) : null}
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </CollapsibleContent>
            </section>
        </Collapsible>
    );
}
