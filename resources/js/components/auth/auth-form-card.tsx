import type { ReactNode } from 'react';

const cardClassName =
    'w-[370px] rounded-[28px] bg-surface px-5 py-5 shadow-soft ring-1 ring-border/80';

export const authSubmitButtonClassName =
    'btn-cta-green h-12 w-full rounded-pill text-sm font-bold text-white';

export function AuthFormCard({ children }: { children: ReactNode }) {
    return <div className={cardClassName}>{children}</div>;
}

export const authFieldClassName =
    'h-11 rounded-2xl border-border bg-surface text-sm text-text shadow-xs ring-1 ring-border placeholder:text-muted/80 focus-visible:border-purple focus-visible:ring-purple/25';

export const authLabelClassName = 'text-sm font-bold text-text';
