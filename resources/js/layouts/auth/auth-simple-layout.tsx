import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({ children }: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col bg-bg px-4 py-5">
            <div className="mx-auto flex w-full max-w-[390px] flex-1 flex-col items-center justify-center gap-5">
                {children}
            </div>
        </div>
    );
}
