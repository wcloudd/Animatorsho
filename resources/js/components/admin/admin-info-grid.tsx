import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type AdminInfoGridProps = {
    children: ReactNode;
    className?: string;
};

export function AdminInfoGrid({ children, className }: AdminInfoGridProps) {
    return (
        <dl
            className={cn(
                'grid grid-cols-1 gap-2 sm:grid-cols-2',
                className,
            )}
        >
            {children}
        </dl>
    );
}
