import type { ReactNode } from 'react';
import { AuthBackLink } from '@/components/auth/auth-back-link';
import { AuthBrandTitle } from '@/components/auth/auth-brand-title';
import { AuthPageHeader } from '@/components/auth/auth-page-header';

type AuthPageIntroProps = {
    title: string;
    subtitle?: ReactNode;
};

export function AuthPageIntro({ title, subtitle }: AuthPageIntroProps) {
    return (
        <div className="flex flex-row flex-wrap items-center gap-4">
            <AuthBrandTitle />
            <AuthPageHeader title={title} subtitle={subtitle} />
            <AuthBackLink />
        </div>
    );
}
