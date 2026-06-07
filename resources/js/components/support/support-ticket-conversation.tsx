import type { SupportTicketMessage } from '@/types/support';
import { SupportMessageAttachmentCard } from '@/components/support/support-message-attachment';
import { cn } from '@/lib/utils';

type SupportTicketConversationProps = {
    messages: SupportTicketMessage[];
    className?: string;
};

export function SupportTicketConversation({
    messages,
    className,
}: SupportTicketConversationProps) {
    if (messages.length === 0) {
        return (
            <p className={cn('text-sm text-muted', className)}>
                هنوز پیامی ثبت نشده است.
            </p>
        );
    }

    return (
        <div className={cn('flex flex-col gap-3', className)}>
            {messages.map((message) => {
                const isUser = message.senderType === 'user';

                return (
                    <article
                        key={message.id}
                        className={cn(
                            'flex max-w-[92%] flex-col gap-2 rounded-[20px] px-4 py-3',
                            isUser
                                ? 'ms-auto bg-purple-soft text-text'
                                : 'me-auto bg-surface ring-1 ring-border',
                        )}
                    >
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-xs font-bold text-purple">
                                {message.senderLabel}
                            </span>
                            {message.createdAt ? (
                                <time className="text-[11px] text-muted">
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
