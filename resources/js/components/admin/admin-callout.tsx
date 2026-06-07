import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type AdminCalloutVariant = 'error' | 'warning';

export const adminCalloutStyles: Record<
    AdminCalloutVariant,
    { box: string; title: string }
> = {
    error: {
        box: 'rounded-xl bg-red/10 px-3 py-2 ring-1 ring-red/15',
        title: 'text-xs font-medium text-red/80',
    },
    warning: {
        box: 'rounded-xl bg-red/8 px-3 py-2 ring-1 ring-red/12',
        title: 'text-xs font-medium text-red/75',
    },
};

type AdminCalloutProps = {
    title?: string;
    variant?: AdminCalloutVariant;
    children: ReactNode;
    className?: string;
};

export function AdminCallout({
    title,
    variant = 'error',
    children,
    className,
}: AdminCalloutProps) {
    const styles = adminCalloutStyles[variant];

    return (
        <div className={cn(styles.box, className)}>
            {title ? <p className={styles.title}>{title}</p> : null}
            <div className={cn(title && 'mt-1', 'text-sm text-text')}>
                {children}
            </div>
        </div>
    );
}
