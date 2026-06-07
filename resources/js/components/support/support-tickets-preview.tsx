import { Link } from '@inertiajs/react';
import { ProfileSectionCard } from '@/components/profile/profile-section-card';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type { SupportTicketListItem } from '@/types/support';
import support from '@/routes/support';
import { cn } from '@/lib/utils';

type SupportTicketsPreviewProps = {
    tickets: SupportTicketListItem[];
};

function isWaitingForUser(ticket: SupportTicketListItem): boolean {
    return ticket.statusValue === 'waiting_user';
}

export function SupportTicketsPreview({ tickets }: SupportTicketsPreviewProps) {
    return (
        <ProfileSectionCard title="پیام‌های من">
            {tickets.length === 0 ? (
                <div className="flex flex-col gap-2 rounded-2xl bg-surface-warm p-4 text-sm font-medium leading-relaxed text-muted ring-1 ring-border/70">
                    <p>هنوز پیامی ثبت نکرده‌ای.</p>
                    <p>برای شروع، فرم ارسال پیام جدید را پر کن.</p>
                </div>
            ) : (
                <ul className="flex flex-col divide-y divide-border/60">
                    {tickets.map((ticket) => (
                        <li key={ticket.id}>
                            <Link
                                href={support.tickets.show(ticket.id)}
                                className={cn(
                                    'flex flex-col gap-2 py-3 first:pt-0 last:pb-0',
                                    isWaitingForUser(ticket) &&
                                        '-mx-1 rounded-2xl border-s-4 border-gold bg-gold-soft/40 px-3',
                                )}
                            >
                                <div className="flex min-w-0 items-start justify-between gap-2">
                                    <span className="min-w-0 flex-1 text-sm font-bold text-text">
                                        {ticket.subject}
                                    </span>
                                    <ProfileStatusBadge tone={ticket.statusTone}>
                                        {ticket.status}
                                    </ProfileStatusBadge>
                                </div>
                                <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
                                    <span>{ticket.category}</span>
                                    {ticket.createdAt ? (
                                        <span>· {ticket.createdAt}</span>
                                    ) : null}
                                </div>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </ProfileSectionCard>
    );
}
