import { Head } from '@inertiajs/react';
import { PageContainer } from '@/components/page-container';
import { SupportHelpNoteCard } from '@/components/support/support-help-note-card';
import { SupportNewTicketForm } from '@/components/support/support-new-ticket-form';
import { SupportPageHeader } from '@/components/support/support-page-header';
import { SupportTicketsPreview } from '@/components/support/support-tickets-preview';
import type { SupportIndexProps } from '@/types/support';

export default function SupportIndex({
    tickets,
    categoryOptions,
    helpNote,
}: SupportIndexProps) {
    const hasTickets = tickets.length > 0;

    const onboardingSections = (
        <>
            <SupportHelpNoteCard helpNote={helpNote} />
            <SupportNewTicketForm categoryOptions={categoryOptions} />
        </>
    );

    return (
        <>
            <Head title="پشتیبانی انیماتورشو" />
            <PageContainer>
                <div className="flex flex-col gap-6">
                    <SupportPageHeader />

                    {hasTickets ? (
                        <>
                            <SupportTicketsPreview tickets={tickets} />
                            {onboardingSections}
                        </>
                    ) : (
                        <>
                            {onboardingSections}
                            <SupportTicketsPreview tickets={tickets} />
                        </>
                    )}
                </div>
            </PageContainer>
        </>
    );
}
