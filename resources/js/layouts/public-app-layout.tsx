import type { ReactNode } from 'react';
import { MobileAppShell } from '@/components/mobile-app-shell';

export default function PublicAppLayout({
    children,
}: {
    children: ReactNode;
}) {
    return <MobileAppShell>{children}</MobileAppShell>;
}
