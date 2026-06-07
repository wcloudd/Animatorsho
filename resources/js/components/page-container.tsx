import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export const surfaceCardClassName =
    'rounded-2xl bg-surface-warm p-6 shadow-soft ring-1 ring-border';

type Props = {
    children: ReactNode;
    className?: string;
};

export function PageContainer({ children, className }: Props) {
    return (
        <div
            className={cn(
                'mx-auto w-full max-w-[390px] overflow-x-hidden px-4 pt-6 pb-32',
                className,
            )}
        >
            {children}
        </div>
    );
}
