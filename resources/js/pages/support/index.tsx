import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { PageContainer } from '@/components/page-container';
import { SupportHelpNoteCard } from '@/components/support/support-help-note-card';
import { SupportNewTicketForm } from '@/components/support/support-new-ticket-form';
import { SupportPageHeader } from '@/components/support/support-page-header';
import { SupportQuickHelpCards } from '@/components/support/support-quick-help-cards';
import { SupportTicketsPreview } from '@/components/support/support-tickets-preview';
import { categoryForQuickHelpItem } from '@/lib/support-quick-help-categories';
import type { SupportIndexProps } from '@/types/support';

export default function SupportIndex({
    tickets,
    categoryOptions,
    quickHelpItems,
    helpNote,
}: SupportIndexProps) {
    const hasTickets = tickets.length > 0;
    const defaultCategory = categoryOptions[0]?.value ?? '';
    const [selectedCategory, setSelectedCategory] = useState(defaultCategory);

    const handleQuickHelpSelect = (itemId: string) => {
        const mappedCategory = categoryForQuickHelpItem(itemId);

        if (mappedCategory !== null) {
            setSelectedCategory(mappedCategory);
        }
    };

    const onboardingSections = (
        <>
            <SupportQuickHelpCards
                items={quickHelpItems}
                onItemSelect={handleQuickHelpSelect}
            />
            <SupportHelpNoteCard helpNote={helpNote} />
            <SupportNewTicketForm
                categoryOptions={categoryOptions}
                category={selectedCategory}
                onCategoryChange={setSelectedCategory}
            />
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
