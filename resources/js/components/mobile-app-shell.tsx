import type { ReactNode } from 'react';
import { BottomNav } from '@/components/bottom-nav';

type Props = {
    children: ReactNode;
};

export function MobileAppShell({ children }: Props) {
    return (
        <div className="flex min-h-dvh w-full flex-col overflow-x-hidden bg-bg">
            <main className="flex-1">{children}</main>
            <BottomNav />
        </div>
    );
}
