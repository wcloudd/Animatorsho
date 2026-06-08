import { usePage } from '@inertiajs/react';
import { ConsultationForm } from '@/components/consultation/consultation-form';
import { ConsultationIntroCard } from '@/components/consultation/consultation-intro-card';
import { ConsultationPageHeader } from '@/components/consultation/consultation-page-header';
import { ConsultationSupportFallbackCard } from '@/components/consultation/consultation-support-fallback-card';
import { ConsultationTrustNote } from '@/components/consultation/consultation-trust-note';
import { PageContainer } from '@/components/page-container';
import { SeoHead } from '@/components/seo/seo-head';
import { PUBLIC_PAGE_SEO, canonicalFromPath, defaultOpenGraph } from '@/lib/seo';
import type { SharedPageProps } from '@/types/seo';

export default function ConsultationIndex() {
    const { appUrl } = usePage<SharedPageProps>().props;
    const meta = PUBLIC_PAGE_SEO.consultation;

    return (
        <>
            <SeoHead
                title={meta.title}
                description={meta.description}
                canonical={canonicalFromPath(appUrl, '/consultation')}
                openGraph={defaultOpenGraph(appUrl, {
                    title: meta.title,
                    description: meta.description,
                    path: '/consultation',
                })}
            />
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
