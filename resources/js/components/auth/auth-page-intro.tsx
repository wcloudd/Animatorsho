import { AuthBackLink } from '@/components/auth/auth-back-link';
import { AuthIllustration } from '@/components/auth/auth-illustration';
import { AuthPageHeader } from '@/components/auth/auth-page-header';

type AuthPageIntroProps = {
    title: string;
    subtitle: string;
};

export function AuthPageIntro({ title, subtitle }: AuthPageIntroProps) {
    return (
        <div className="flex flex-col items-center gap-4">
            <AuthIllustration />
            <AuthPageHeader title={title} subtitle={subtitle} />
            <AuthBackLink />
        </div>
    );
}
