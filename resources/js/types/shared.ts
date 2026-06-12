import type { Auth } from '@/types/auth';

export type SharedPageProps = {
    name: string;
    appUrl: string;
    auth: Auth;
    nav?: {
        animatorshoHref?: string;
    };
    sidebarOpen: boolean;
    security?: {
        honeypot: {
            enabled: boolean;
            fieldName: string;
        };
    };
};
