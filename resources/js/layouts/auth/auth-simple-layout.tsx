import { Link } from '@inertiajs/react';
import {
    ANIMATORSHO_LOGO_SRC,
    AUTH_BACK_TO_HOME_LABEL,
} from '@/lib/auth-form-data';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({ children }: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col bg-bg px-4 py-6">
            <div className="mx-auto flex w-full max-w-[390px] flex-1 flex-col gap-6">
                <div className="flex flex-col items-center gap-4">
                    <Link
                        href={home()}
                        className="flex flex-col items-center gap-3"
                    >
                        <img
                            src={ANIMATORSHO_LOGO_SRC}
                            alt="انیماتورشو"
                            className="h-12 w-auto"
                        />
                    </Link>

                    <Link
                        href={home()}
                        className="text-sm font-medium text-purple transition-colors hover:text-text"
                    >
                        {AUTH_BACK_TO_HOME_LABEL}
                    </Link>
                </div>

                <div className="flex flex-1 flex-col gap-6">{children}</div>
            </div>
        </div>
    );
}
