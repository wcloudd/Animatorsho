import { Link } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { AdminButton } from '@/components/admin/admin-button';
import { cn } from '@/lib/utils';

type AdminSearchBarProps = {
    basePath: string;
    placeholder: string;
    value: string | null;
    hiddenParams?: Record<string, string | null | undefined>;
    filters?: ReactNode;
    embedded?: boolean;
};

function buildSearchHref(
    basePath: string,
    params: Record<string, string | null | undefined>,
): string {
    const searchParams = new URLSearchParams();

    Object.entries(params).forEach(([key, paramValue]) => {
        if (paramValue !== null && paramValue !== undefined && paramValue !== '') {
            searchParams.set(key, paramValue);
        }
    });

    const query = searchParams.toString();

    return query ? `${basePath}?${query}` : basePath;
}

export function AdminSearchBar({
    basePath,
    placeholder,
    value,
    hiddenParams = {},
    filters,
    embedded = false,
}: AdminSearchBarProps) {
    const [query, setQuery] = useState(value ?? '');
    const hasActiveSearch = Boolean(value && value.trim() !== '');

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();

        const trimmed = query.trim();

        if (trimmed.length < 2) {
            return;
        }

        window.location.href = buildSearchHref(basePath, {
            ...hiddenParams,
            q: trimmed,
        });
    };

    const clearHref = buildSearchHref(basePath, hiddenParams);

    const content = (
        <>
            <form onSubmit={handleSubmit} className="flex gap-2">
                <input
                    type="search"
                    name="q"
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder={placeholder}
                    className={cn(
                        'h-10 min-w-0 flex-1 rounded-xl border border-[#e8e0f0] bg-surface px-3 text-sm text-text shadow-xs outline-none placeholder:text-muted focus-visible:ring-[3px] focus-visible:ring-purple/30',
                    )}
                    autoComplete="off"
                />
                <AdminButton type="submit" size="sm" adminVariant="brand">
                    جستجو
                </AdminButton>
                {hasActiveSearch ? (
                    <AdminButton asChild size="sm" adminVariant="outline">
                        <Link href={clearHref} preserveState>
                            پاک کردن
                        </Link>
                    </AdminButton>
                ) : null}
            </form>
            {filters ? (
                <div className="flex flex-col gap-2 border-t border-purple/10 pt-3">
                    {filters}
                </div>
            ) : null}
        </>
    );

    if (embedded) {
        return content;
    }

    return (
        <div
            className={cn(
                'mb-5 flex flex-col gap-3 rounded-2xl bg-surface-warm p-3 shadow-soft ring-1 ring-purple/10',
            )}
        >
            {content}
        </div>
    );
}
