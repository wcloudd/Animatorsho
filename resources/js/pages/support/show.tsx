import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { PageContainer } from '@/components/page-container';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import { SupportTicketConversation } from '@/components/support/support-ticket-conversation';
import { SupportTicketMessageForm } from '@/components/support/support-ticket-message-form';
import type { SupportShowProps } from '@/types/support';

export default function SupportShow({ ticket, messages }: SupportShowProps) {
    return (
        <>
            <Head title={`پشتیبانی — ${ticket.subject}`} />
            <PageContainer>
                <div className="flex flex-col gap-6">
                    <Link
                        href="/support"
                        className="inline-flex items-center gap-1 text-sm font-medium text-purple"
                    >
                        <ArrowRight className="size-4" aria-hidden />
                        بازگشت به پشتیبانی
                    </Link>

                    <header className="flex flex-col gap-3 rounded-[28px] bg-surface px-5 py-5 shadow-soft ring-1 ring-border">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <h1 className="font-liana text-lg text-text">
                                {ticket.subject}
                            </h1>
                            <ProfileStatusBadge tone={ticket.statusTone}>
                                {ticket.status}
                            </ProfileStatusBadge>
                        </div>
                        <div className="flex flex-wrap gap-2 text-xs text-muted">
                            <span>{ticket.category}</span>
                            {ticket.createdAt ? (
                                <span>· {ticket.createdAt}</span>
                            ) : null}
                        </div>
                    </header>

                    <section className="rounded-[28px] bg-surface px-5 py-5 shadow-soft ring-1 ring-border">
                        <h2 className="mb-4 text-base font-bold text-text">
                            گفتگو
                        </h2>
                        <SupportTicketConversation messages={messages} />
                    </section>

                    {ticket.canReply ? (
                        <SupportTicketMessageForm
                            action={`/support/tickets/${ticket.id}/messages`}
                        />
                    ) : (
                        <p className="rounded-[20px] bg-gold-soft px-4 py-3 text-sm text-text">
                            این تیکت بسته شده و امکان ارسال پاسخ جدید وجود
                            ندارد.
                        </p>
                    )}
                </div>
            </PageContainer>
        </>
    );
}
