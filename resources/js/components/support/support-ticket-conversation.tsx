import type { SupportTicketMessage } from '@/types/support';
import { SupportMessageAttachmentCard } from '@/components/support/support-message-attachment';
import { cn } from '@/lib/utils';

type SupportTicketConversationProps = {
    messages: SupportTicketMessage[];
    className?: string;
};

function senderLabelClassName(senderType: string): string {
    if (senderType === 'user') {
        return 'text-purple';
    }

    if (senderType === 'admin') {
        return 'text-blue';
    }

    return 'text-muted';
}

export function SupportTicketConversation({
    messages,
    className,
}: SupportTicketConversationProps) {
    if (messages.length === 0) {
        return (
            <div
                className={cn(
                    'flex flex-col gap-2 rounded-2xl bg-surface-warm p-4 text-sm font-medium leading-relaxed text-muted ring-1 ring-border/70',
                    className,
                )}
            >
                <p>هنوز پیامی ثبت نشده است.</p>
            </div>
        );
    }

    return (
        <div className={cn('flex flex-col gap-4', className)}>
            {messages.map((message) => {
                const isUser = message.senderType === 'user';
                const isAdmin = message.senderType === 'admin';

                return (
                    <article
                        key={message.id}
                        className={cn(
                            'flex min-w-0 max-w-[92%] flex-col gap-2 rounded-[20px] px-4 py-3 break-words',
                            isUser
                                ? 'ms-auto bg-purple-soft text-text'
                                : isAdmin
                                  ? 'me-auto bg-surface ring-2 ring-blue/20'
                                  : 'me-auto bg-surface ring-1 ring-border',
                        )}
                    >
                        <div className="flex items-center justify-between gap-2">
                            <span
                                className={cn(
                                    'text-xs font-bold',
                                    senderLabelClassName(message.senderType),
                                )}
                            >
                                {message.senderLabel}
                            </span>
                            {message.createdAt ? (
                                <time className="shrink-0 text-xs text-muted">
                                    {message.createdAt}
                                </time>
                            ) : null}
                        </div>
                        <p className="whitespace-pre-wrap text-sm leading-6 text-text">
                            {message.body}
                        </p>
                        {message.attachment ? (
                            <SupportMessageAttachmentCard
                                attachment={message.attachment}
                            />
                        ) : null}
                    </article>
                );
            })}
        </div>
    );
}
