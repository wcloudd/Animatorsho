import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import { MobileAppShell } from '@/components/mobile-app-shell';
import { NoIndexSeoHead } from '@/components/seo/seo-head';
import { shouldNoIndexPublicTabComponent } from '@/lib/seo-noindex';

export default function PublicAppLayout({
    children,
}: {
    children: ReactNode;
}) {
    const { component } = usePage();

    return (
        <>
            {shouldNoIndexPublicTabComponent(component) ? (
                <NoIndexSeoHead />
            ) : null}
            <MobileAppShell>{children}</MobileAppShell>
        </>
    );
}
