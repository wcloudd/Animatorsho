import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type AdminPageHeaderProps = {
    title: string;
    description?: string;
    actions?: ReactNode;
    className?: string;
};

export function AdminPageHeader({
    title,
    description,
    actions,
    className,
}: AdminPageHeaderProps) {
    return (
        <div
            className={cn(
                'mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between',
                className,
            )}
        >
            <div className="flex min-w-0 gap-3">
                <span
                    className="mt-1 hidden h-8 w-1 shrink-0 rounded-full bg-purple/80 sm:block"
                    aria-hidden
                />
                <div className="min-w-0">
                    <h2 className="font-liana text-xl text-text">{title}</h2>
                    {description ? (
                        <p className="mt-1 text-sm leading-relaxed text-muted">
                            {description}
                        </p>
                    ) : null}
                </div>
            </div>
            {actions ? (
                <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>
            ) : null}
        </div>
    );
}
