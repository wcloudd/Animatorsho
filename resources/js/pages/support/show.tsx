import { Head, Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { PageContainer } from '@/components/page-container';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import { ProfileSectionCard } from '@/components/profile/profile-section-card';
import { SupportTicketConversation } from '@/components/support/support-ticket-conversation';
import { SupportTicketMessageForm } from '@/components/support/support-ticket-message-form';
import type { SupportShowProps } from '@/types/support';
import support from '@/routes/support';
import { cn } from '@/lib/utils';

export default function SupportShow({ ticket, messages }: SupportShowProps) {
    const isWaitingForUser = ticket.statusValue === 'waiting_user';

    return (
        <>
            <Head title={`پشتیبانی — ${ticket.subject}`} />
            <PageContainer>
                <div className="flex flex-col gap-6">
                    <header className="flex flex-col gap-3">
                        <Link
                            href={support.index()}
                            className="inline-flex w-fit items-center gap-1 text-sm font-medium text-muted transition-colors hover:text-purple"
                        >
                            <ChevronRight
                                className="size-4 rotate-180"
                                aria-hidden
                            />
                            بازگشت به پشتیبانی
                        </Link>

                        <div className="flex flex-col gap-3 rounded-[28px] bg-surface px-5 py-5 shadow-soft ring-1 ring-border">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <h1 className="font-display text-[1.25rem] font-bold leading-tight text-text">
                                    {ticket.subject}
                                </h1>
                                <ProfileStatusBadge tone={ticket.statusTone}>
                                    {ticket.status}
                                </ProfileStatusBadge>
                            </div>
                            <div className="flex flex-wrap items-center gap-2 text-xs font-medium text-muted">
                                <span className="rounded-pill bg-purple-soft px-2.5 py-1 text-purple ring-1 ring-purple/15">
                                    {ticket.category}
                                </span>
                                {ticket.createdAt ? (
                                    <time>{ticket.createdAt}</time>
                                ) : null}
                            </div>
                        </div>
                    </header>

                    {isWaitingForUser ? (
                        <div
                            className="rounded-[28px] bg-gold-soft px-5 py-4 shadow-soft ring-1 ring-gold/20"
                            role="status"
                        >
                            <p className="text-sm font-bold text-text">
                                پشتیبانی منتظر پاسخ شماست
                            </p>
                            <p className="mt-1 text-sm font-medium leading-relaxed text-muted">
                                لطفاً در بخش پایین صفحه پاسخ خود را ارسال کنید.
                            </p>
                        </div>
                    ) : null}

                    <ProfileSectionCard title="گفتگو">
                        <SupportTicketConversation messages={messages} />
                    </ProfileSectionCard>

                    {ticket.canReply ? (
                        <ProfileSectionCard
                            title="ارسال پاسخ"
                            description="پاسخ خود را بنویس و در صورت نیاز فایل پیوست کن."
                        >
                            <SupportTicketMessageForm
                                action={support.tickets.messages.store.url(
                                    ticket.id,
                                )}
                                unstyled
                            />
                        </ProfileSectionCard>
                    ) : (
                        <article
                            className={cn(
                                'flex flex-col gap-2 rounded-[28px] bg-gold-soft px-5 py-4 shadow-soft ring-1 ring-gold/20',
                            )}
                        >
                            <h2 className="text-sm font-bold text-text">
                                تیکت بسته شده
                            </h2>
                            <p className="text-sm font-medium leading-relaxed text-muted">
                                این تیکت بسته شده و امکان ارسال پاسخ جدید وجود
                                ندارد.
                            </p>
                        </article>
                    )}
                </div>
            </PageContainer>
        </>
    );
}
