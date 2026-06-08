import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { NoIndexSeoHead } from '@/components/seo/seo-head';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import { shouldNoIndexAuthComponent } from '@/lib/seo-noindex';

export default function AuthLayout({ children }: { children: ReactNode }) {
    const { component } = usePage();

    return (
        <>
            {shouldNoIndexAuthComponent(component) ? <NoIndexSeoHead /> : null}
            <AuthLayoutTemplate>{children}</AuthLayoutTemplate>
        </>
    );
}
