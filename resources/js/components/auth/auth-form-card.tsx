import type { ReactNode } from 'react';

const cardClassName =
    'rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border';

export function AuthFormCard({ children }: { children: ReactNode }) {
    return <div className={cardClassName}>{children}</div>;
}

export const authFieldClassName =
    'h-11 rounded-2xl border-border bg-surface text-sm text-text shadow-xs ring-1 ring-border placeholder:text-muted/80 focus-visible:border-purple focus-visible:ring-purple/25';

export const authLabelClassName = 'text-sm font-bold text-text';
