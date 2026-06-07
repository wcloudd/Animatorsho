import { Head } from '@inertiajs/react';
import { PageContainer } from '@/components/page-container';
import { SupportHelpNoteCard } from '@/components/support/support-help-note-card';
import { SupportNewTicketForm } from '@/components/support/support-new-ticket-form';
import { SupportPageHeader } from '@/components/support/support-page-header';
import { SupportQuickHelpCards } from '@/components/support/support-quick-help-cards';
import { SupportTicketsPreview } from '@/components/support/support-tickets-preview';
import type { SupportIndexProps } from '@/types/support';

export default function SupportIndex({
    tickets,
    categoryOptions,
    quickHelpItems,
    helpNote,
}: SupportIndexProps) {
    return (
        <>
            <Head title="پشتیبانی انیماتورشو" />
            <PageContainer>
                <div className="flex flex-col gap-6">
                    <SupportPageHeader />
                    <SupportQuickHelpCards items={quickHelpItems} />
                    <SupportHelpNoteCard helpNote={helpNote} />
                    <SupportNewTicketForm
                        categoryOptions={categoryOptions}
                        storeUrl="/support/tickets"
                    />
                    <SupportTicketsPreview tickets={tickets} />
                </div>
            </PageContainer>
        </>
    );
}
