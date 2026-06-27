import type { ReactNode } from 'react';
import { AuthBackLink } from '@/components/auth/auth-back-link';
import { AuthIllustration } from '@/components/auth/auth-illustration';
import { AuthPageHeader } from '@/components/auth/auth-page-header';

type AuthPageIntroProps = {
    title: string;
    subtitle?: ReactNode;
    /** Optional override for the logo mark (defaults to the shared illustration). */
    mark?: ReactNode;
};

export function AuthPageIntro({ title, subtitle, mark }: AuthPageIntroProps) {
    return (
        <div className="flex flex-row flex-wrap items-center gap-4">
            {mark ?? <AuthIllustration />}
            <AuthPageHeader title={title} subtitle={subtitle} />
            <AuthBackLink />
        </div>
    );
}
