import { Link } from '@inertiajs/react';
import { AuthBackLink } from '@/components/auth/auth-back-link';
import { AuthIllustration } from '@/components/auth/auth-illustration';
import { ANIMATORSHO_LOGO_SRC } from '@/lib/auth-form-data';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({ children }: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col bg-bg px-4 py-5">
            <div className="mx-auto flex w-full max-w-[390px] flex-1 flex-col gap-5">
                <div className="flex flex-col gap-4">
                    <AuthBackLink />

                    <div className="flex flex-col items-center gap-4">
                        <Link href={home()} className="flex flex-col items-center">
                            <img
                                src={ANIMATORSHO_LOGO_SRC}
                                alt="انیماتورشو"
                                className="h-11 w-auto"
                            />
                        </Link>

                        <AuthIllustration />
                    </div>
                </div>

                <div className="flex flex-1 flex-col gap-5">{children}</div>
            </div>
        </div>
    );
}
