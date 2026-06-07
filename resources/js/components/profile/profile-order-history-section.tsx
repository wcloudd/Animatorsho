import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import type { ProfileOrderHistoryItem } from '@/lib/profile-data';
import { formatProfileDate } from '@/lib/format-profile-date';
import { formatTomanPrice } from '@/lib/format-toman';
import { ProfileSectionCard } from '@/components/profile/profile-section-card';
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
}: ProfileOrderHistorySectionProps) {
    const [open, setOpen] = useState(false);
    const isEmpty = orderHistory.length === 0;

    if (isEmpty) {
        return (
            <ProfileSectionCard
                title="سوابق سفارش‌ها"
                description="تاریخچه سفارش‌ها و پرداخت‌های قبلی شما."
            >
                <div className="flex flex-col gap-2 rounded-2xl bg-surface-warm p-4 text-sm font-medium leading-relaxed text-muted ring-1 ring-border/70">
                    <p>هنوز سفارشی ثبت نشده است.</p>
                    <p>
                        بعد از ثبت‌نام در دوره، سوابق سفارش‌ها اینجا نمایش داده
                        می‌شود.
                    </p>
                </div>
            </ProfileSectionCard>
        );
    }

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <ProfileSectionCard
                title="سوابق سفارش‌ها"
                description={`${orderHistory.length} سفارش`}
                className="gap-0"
            >
                <CollapsibleTrigger
                    className="flex w-full items-center justify-between gap-3 rounded-2xl bg-surface-warm px-4 py-3 text-right ring-1 ring-border/70"
                    aria-expanded={open}
                >
                    <span className="text-sm font-bold text-text">
                        {open ? 'بستن سوابق' : 'مشاهده سوابق سفارش‌ها'}
                    </span>
                    <ChevronDown
                        className={cn(
                            'size-4 shrink-0 text-muted transition-transform duration-200',
                            open && 'rotate-180',
                        )}
                        aria-hidden
                    />
                </CollapsibleTrigger>

                <CollapsibleContent className="pt-4">
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
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div className="flex min-w-0 flex-1 flex-col gap-1">
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

                                    <ul className="flex flex-col gap-1 text-xs font-medium text-muted">
                                        <li>{order.paymentType}</li>
                                        {order.paymentMethod ? (
                                            <li>{order.paymentMethod}</li>
                                        ) : null}
                                        <li>
                                            {formatTomanPrice(
                                                order.amountToman,
                                            )}
                                        </li>
                                        {createdAtLabel ? (
                                            <li>{createdAtLabel}</li>
                                        ) : null}
                                    </ul>
                                </li>
                            );
                        })}
                    </ul>
                </CollapsibleContent>
            </ProfileSectionCard>
        </Collapsible>
    );
}
