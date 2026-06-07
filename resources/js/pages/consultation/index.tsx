import { Head } from '@inertiajs/react';
import { ConsultationForm } from '@/components/consultation/consultation-form';
import { ConsultationIntroCard } from '@/components/consultation/consultation-intro-card';
import { ConsultationPageHeader } from '@/components/consultation/consultation-page-header';
import { ConsultationSupportFallbackCard } from '@/components/consultation/consultation-support-fallback-card';
import { ConsultationTrustNote } from '@/components/consultation/consultation-trust-note';
import { PageContainer } from '@/components/page-container';

export default function ConsultationIndex() {
    return (
        <>
            <Head title="مشاوره رایگان انیماتورشو" />
            <PageContainer>
                <div className="flex flex-col gap-6">
                    <ConsultationPageHeader />
                    <ConsultationIntroCard />
                    <ConsultationForm />
                    <ConsultationTrustNote />
                    <ConsultationSupportFallbackCard />
                </div>
            </PageContainer>
        </>
    );
}
