import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type AdminSectionTitleProps = {
    children: ReactNode;
    className?: string;
};

export function AdminSectionTitle({
    children,
    className,
}: AdminSectionTitleProps) {
    return (
        <h3
            className={cn(
                'mb-3 flex items-center gap-2 text-sm font-bold text-text',
                className,
            )}
        >
            <span
                className="h-4 w-1 shrink-0 rounded-full bg-purple/70"
                aria-hidden
            />
            {children}
        </h3>
    );
}
