import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { AdminStatusOption } from '@/types/admin';

type AdminFilterBarProps = {
    basePath: string;
    options: AdminStatusOption[];
    currentStatus: string | null;
    allLabel?: string;
};

export function AdminFilterBar({
    basePath,
    options,
    currentStatus,
    allLabel = 'همه',
}: AdminFilterBarProps) {
    const isAllActive = !currentStatus;

    return (
        <div className="mb-4 flex flex-wrap gap-2">
            <Link
                href={basePath}
                className={cn(
                    'rounded-pill px-3 py-1 text-xs font-medium transition',
                    isAllActive
                        ? 'bg-purple text-white'
                        : 'bg-surface text-muted ring-1 ring-purple/10 hover:text-purple',
                )}
                preserveState
            >
                {allLabel}
            </Link>
            {options.map((option) => (
                <Link
                    key={option.value}
                    href={`${basePath}?status=${option.value}`}
                    className={cn(
                        'rounded-pill px-3 py-1 text-xs font-medium transition',
                        currentStatus === option.value
                            ? 'bg-purple text-white'
                            : 'bg-surface text-muted ring-1 ring-purple/10 hover:text-purple',
                    )}
                    preserveState
                >
                    {option.label}
                </Link>
            ))}
        </div>
    );
}
