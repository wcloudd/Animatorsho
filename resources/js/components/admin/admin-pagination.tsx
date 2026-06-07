import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { AdminPaginated } from '@/types/admin';

type AdminPaginationProps<T> = {
    paginator: AdminPaginated<T>;
};

export function AdminPagination<T>({ paginator }: AdminPaginationProps<T>) {
    if (paginator.last_page <= 1) {
        return null;
    }

    return (
        <div className="mt-6 flex flex-wrap items-center justify-center gap-1.5">
            {paginator.links.map((link, index) => {
                if (link.url === null) {
                    return (
                        <span
                            key={`${link.label}-${index}`}
                            className="px-2 py-1 text-sm text-muted"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    );
                }

                return (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        className={cn(
                            'rounded-lg px-3 py-1.5 text-sm transition',
                            link.active
                                ? 'bg-purple text-white shadow-xs'
                                : 'text-muted hover:bg-purple-soft hover:text-purple',
                        )}
                        preserveState
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                );
            })}
        </div>
    );
}
