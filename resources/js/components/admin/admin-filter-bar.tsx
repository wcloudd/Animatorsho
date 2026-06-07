import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { AdminStatusOption } from '@/types/admin';

type AdminFilterBarProps = {
    basePath: string;
    options: AdminStatusOption[];
    currentStatus: string | null;
    allLabel?: string;
    searchQuery?: string | null;
    paramKey?: string;
    extraParams?: Record<string, string | null | undefined>;
    label?: string;
};

function buildFilterHref(
    basePath: string,
    paramKey: string,
    status: string | null,
    searchQuery?: string | null,
    extraParams: Record<string, string | null | undefined> = {},
): string {
    const params = new URLSearchParams();

    if (status) {
        params.set(paramKey, status);
    }

    if (searchQuery) {
        params.set('q', searchQuery);
    }

    Object.entries(extraParams).forEach(([key, paramValue]) => {
        if (
            key !== paramKey &&
            paramValue !== null &&
            paramValue !== undefined &&
            paramValue !== ''
        ) {
            params.set(key, paramValue);
        }
    });

    const query = params.toString();

    return query ? `${basePath}?${query}` : basePath;
}

export function AdminFilterBar({
    basePath,
    options,
    currentStatus,
    allLabel = 'همه',
    searchQuery = null,
    paramKey = 'status',
    extraParams = {},
    label,
}: AdminFilterBarProps) {
    const isAllActive = !currentStatus;

    return (
        <div className="flex flex-col gap-1.5">
            {label ? (
                <span className="text-xs font-medium text-muted">{label}</span>
            ) : null}
            <div className="flex flex-wrap gap-1.5">
                <Link
                    href={buildFilterHref(
                        basePath,
                        paramKey,
                        null,
                        searchQuery,
                        extraParams,
                    )}
                    className={cn(
                        'rounded-pill px-3 py-1.5 text-xs font-medium transition',
                        isAllActive
                            ? 'bg-purple text-white shadow-xs'
                            : 'bg-surface text-muted ring-1 ring-purple/15 hover:text-purple',
                    )}
                    preserveState
                >
                    {allLabel}
                </Link>
                {options.map((option) => (
                    <Link
                        key={option.value}
                        href={buildFilterHref(
                            basePath,
                            paramKey,
                            option.value,
                            searchQuery,
                            extraParams,
                        )}
                        className={cn(
                            'rounded-pill px-3 py-1.5 text-xs font-medium transition',
                            currentStatus === option.value
                                ? 'bg-purple text-white shadow-xs'
                                : 'bg-surface text-muted ring-1 ring-purple/15 hover:text-purple',
                        )}
                        preserveState
                    >
                        {option.label}
                    </Link>
                ))}
            </div>
        </div>
    );
}
