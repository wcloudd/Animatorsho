import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import type { ReactNode } from 'react';

export default function AuthLayout({ children }: { children: ReactNode }) {
    return <AuthLayoutTemplate>{children}</AuthLayoutTemplate>;
}
