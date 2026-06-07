import { Link } from '@inertiajs/react';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type { SupportTicketListItem } from '@/types/support';

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border';

type SupportTicketsPreviewProps = {
    tickets: SupportTicketListItem[];
};

export function SupportTicketsPreview({ tickets }: SupportTicketsPreviewProps) {
    return (
        <article className={cardClassName}>
            <h2 className="text-base font-bold text-text">پیام‌های من</h2>

            {tickets.length === 0 ? (
                <p className="text-sm text-muted">
                    هنوز پیامی ثبت نکرده‌ای. برای شروع، فرم بالا را پر کن.
                </p>
            ) : (
                <ul className="flex flex-col divide-y divide-border/60">
                    {tickets.map((ticket) => (
                        <li key={ticket.id}>
                            <Link
                                href={`/support/tickets/${ticket.id}`}
                                className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div className="flex min-w-0 flex-col gap-1">
                                    <span className="truncate text-sm font-medium text-text">
                                        {ticket.subject}
                                    </span>
                                    <span className="text-xs text-muted">
                                        {ticket.category}
                                        {ticket.createdAt
                                            ? ` · ${ticket.createdAt}`
                                            : ''}
                                    </span>
                                </div>
                                <ProfileStatusBadge tone={ticket.statusTone}>
                                    {ticket.status}
                                </ProfileStatusBadge>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </article>
    );
}
