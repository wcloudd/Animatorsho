import { Link } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { cn } from '@/lib/utils';

type AdminSearchBarProps = {
    basePath: string;
    placeholder: string;
    value: string | null;
    hiddenParams?: Record<string, string | null | undefined>;
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

    return (
        <form onSubmit={handleSubmit} className="mb-4 flex gap-2">
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
            <button
                type="submit"
                className="shrink-0 rounded-xl bg-purple px-4 text-sm font-medium text-white transition hover:bg-purple/90"
            >
                جستجو
            </button>
            {hasActiveSearch ? (
                <Link
                    href={clearHref}
                    className="flex h-10 shrink-0 items-center rounded-xl bg-surface px-3 text-sm font-medium text-muted ring-1 ring-border/70 transition hover:text-purple"
                    preserveState
                >
                    پاک کردن
                </Link>
            ) : null}
        </form>
    );
}
