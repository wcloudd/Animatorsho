import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type AdminActionRowProps = {
    children: ReactNode;
    className?: string;
    bordered?: boolean;
};

export function AdminActionRow({
    children,
    className,
    bordered = true,
}: AdminActionRowProps) {
    return (
        <div
            className={cn(
                'flex flex-col gap-3',
                bordered && 'border-t border-purple/10 pt-3',
                className,
            )}
        >
            {children}
        </div>
    );
}
