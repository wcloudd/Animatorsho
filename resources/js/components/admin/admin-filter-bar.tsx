import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { AdminStatusOption } from '@/types/admin';

type AdminFilterBarProps = {
    basePath: string;
    options: AdminStatusOption[];
    currentStatus: string | null;
    allLabel?: string;
    searchQuery?: string | null;
};

function buildFilterHref(
    basePath: string,
    status: string | null,
    searchQuery?: string | null,
): string {
    const params = new URLSearchParams();

    if (status) {
        params.set('status', status);
    }

    if (searchQuery) {
        params.set('q', searchQuery);
    }

    const query = params.toString();

    return query ? `${basePath}?${query}` : basePath;
}

export function AdminFilterBar({
    basePath,
    options,
    currentStatus,
    allLabel = 'همه',
    searchQuery = null,
}: AdminFilterBarProps) {
    const isAllActive = !currentStatus;

    return (
        <div className="mb-4 flex flex-wrap gap-2">
            <Link
                href={buildFilterHref(basePath, null, searchQuery)}
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
                    href={buildFilterHref(basePath, option.value, searchQuery)}
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
