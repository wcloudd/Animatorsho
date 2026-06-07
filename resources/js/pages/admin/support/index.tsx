import { Head, Link } from '@inertiajs/react';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { AdminSearchBar } from '@/components/admin/admin-search-bar';
import { cn } from '@/lib/utils';
import type {
    AdminPaginated,
    AdminStatusOption,
    AdminSupportTicketListItem,
} from '@/types/admin';

type PageProps = {
    tickets: AdminPaginated<AdminSupportTicketListItem>;
    filters: { status: string | null; category: string | null; q: string | null };
    statusOptions: AdminStatusOption[];
    categoryOptions: AdminStatusOption[];
};

function buildFilterHref(
    basePath: string,
    filters: { status: string | null; category: string | null; q: string | null },
    updates: Partial<{ status: string | null; category: string | null }>,
): string {
    const next = { ...filters, ...updates };
    const params = new URLSearchParams();

    if (next.status) {
        params.set('status', next.status);
    }

    if (next.category) {
        params.set('category', next.category);
    }

    if (next.q) {
        params.set('q', next.q);
    }

    const query = params.toString();

    return query ? `${basePath}?${query}` : basePath;
}

function StatusFilterBar({
    basePath,
    options,
    filters,
}: {
    basePath: string;
    options: AdminStatusOption[];
    filters: { status: string | null; category: string | null; q: string | null };
}) {
    return (
        <div className="flex flex-wrap gap-2">
            <Link
                href={buildFilterHref(basePath, filters, { status: null })}
                className={cn(
                    'rounded-pill px-3 py-1 text-xs font-medium transition',
                    !filters.status
                        ? 'bg-purple text-white'
                        : 'bg-surface text-muted ring-1 ring-purple/10 hover:text-purple',
                )}
                preserveState
            >
                همه
            </Link>
            {options.map((option) => (
                <Link
                    key={option.value}
                    href={buildFilterHref(basePath, filters, {
                        status: option.value,
                    })}
                    className={cn(
                        'rounded-pill px-3 py-1 text-xs font-medium transition',
                        filters.status === option.value
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

function CategoryFilterBar({
    basePath,
    options,
    filters,
}: {
    basePath: string;
    options: AdminStatusOption[];
    filters: { status: string | null; category: string | null; q: string | null };
}) {
    return (
        <div className="flex flex-wrap gap-2">
            <Link
                href={buildFilterHref(basePath, filters, { category: null })}
                className={cn(
                    'rounded-pill px-3 py-1 text-xs font-medium transition',
                    !filters.category
                        ? 'bg-purple text-white'
                        : 'bg-surface text-muted ring-1 ring-purple/10 hover:text-purple',
                )}
                preserveState
            >
                همه دسته‌ها
            </Link>
            {options.map((option) => (
                <Link
                    key={option.value}
                    href={buildFilterHref(basePath, filters, {
                        category: option.value,
                    })}
                    className={cn(
                        'rounded-pill px-3 py-1 text-xs font-medium transition',
                        filters.category === option.value
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

export default function AdminSupportIndex({
    tickets,
    filters,
    statusOptions,
    categoryOptions,
}: PageProps) {
    const basePath = '/admin/support';

    return (
        <>
            <Head title="مدیریت پشتیبانی" />
            <AdminPageHeader
                title="پشتیبانی"
                description="پیگیری تیکت‌های کاربران و پاسخ‌گویی"
            />
            <AdminSearchBar
                basePath={basePath}
                placeholder="جستجو بر اساس موضوع، نام، موبایل، متن..."
                value={filters.q}
                hiddenParams={{
                    status: filters.status,
                    category: filters.category,
                }}
            />
            <div className="mb-4 flex flex-col gap-2">
                <StatusFilterBar
                    basePath={basePath}
                    options={statusOptions}
                    filters={filters}
                />
                <CategoryFilterBar
                    basePath={basePath}
                    options={categoryOptions}
                    filters={filters}
                />
            </div>
            <div className="flex flex-col gap-3">
                {tickets.data.map((ticket) => (
                    <AdminCommerceCard
                        key={ticket.id}
                        title={ticket.subject}
                        subtitle={`${ticket.userName} · ${ticket.category}`}
                        badge={{
                            label: ticket.status,
                            tone: ticket.statusTone,
                        }}
                        headerAction={
                            <Link
                                href={`/admin/support/${ticket.id}`}
                                className="text-xs font-medium text-purple hover:underline"
                            >
                                مشاهده
                            </Link>
                        }
                    >
                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="نام مشتری"
                                value={ticket.customerName}
                            />
                            <AdminDetailRow
                                label="موبایل"
                                value={ticket.customerMobile ?? '—'}
                            />
                            <AdminDetailRow
                                label="ایمیل کاربر"
                                value={ticket.userEmail}
                            />
                            <AdminDetailRow
                                label="تاریخ ثبت"
                                value={ticket.createdAt ?? '—'}
                            />
                        </AdminInfoGrid>
                    </AdminCommerceCard>
                ))}
                {tickets.data.length === 0 ? (
                    <AdminEmptyState
                        message="تیکتی یافت نشد."
                        isSearchActive={Boolean(filters.q)}
                    />
                ) : null}
            </div>
            <AdminPagination paginator={tickets} />
        </>
    );
}
