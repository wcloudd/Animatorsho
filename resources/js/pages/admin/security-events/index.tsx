import { Head, Link } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminFilterBar } from '@/components/admin/admin-filter-bar';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSearchBar } from '@/components/admin/admin-search-bar';
import { SecurityEventsList } from '@/components/admin/security-events-list';
import type {
    AdminPaginated,
    AdminSecurityEventItem,
    AdminStatusOption,
} from '@/types/admin';

type PageProps = {
    events: AdminPaginated<AdminSecurityEventItem>;
    filters: {
        event: string | null;
        from: string | null;
        to: string | null;
        user_id: number | null;
        q: string | null;
    };
    eventOptions: AdminStatusOption[];
};

function buildFilterHref(
    filters: PageProps['filters'],
    overrides: Partial<PageProps['filters']> = {},
): string {
    const merged = { ...filters, ...overrides };
    const params = new URLSearchParams();

    if (merged.event) {
        params.set('event', merged.event);
    }

    if (merged.from) {
        params.set('from', merged.from);
    }

    if (merged.to) {
        params.set('to', merged.to);
    }

    if (merged.user_id) {
        params.set('user_id', String(merged.user_id));
    }

    if (merged.q) {
        params.set('q', merged.q);
    }

    const query = params.toString();

    return query ? `/admin/security-events?${query}` : '/admin/security-events';
}

export default function AdminSecurityEventsIndex({
    events,
    filters,
    eventOptions,
}: PageProps) {
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const [userId, setUserId] = useState(
        filters.user_id ? String(filters.user_id) : '',
    );

    const handleDateSubmit = (event: FormEvent) => {
        event.preventDefault();

        window.location.href = buildFilterHref(filters, {
            from: from.trim() !== '' ? from : null,
            to: to.trim() !== '' ? to : null,
            user_id:
                userId.trim() !== '' && /^\d+$/.test(userId.trim())
                    ? Number(userId.trim())
                    : null,
        });
    };

    const hasAdvancedFilters = Boolean(
        filters.from || filters.to || filters.user_id,
    );

    return (
        <>
            <Head title="رویدادهای امنیتی" />
            <AdminPageHeader
                title="رویدادهای امنیتی"
                description="نمایش read-only رویدادهای مشکوک امنیتی ثبت‌شده"
            />

            <AdminSearchBar
                basePath="/admin/security-events"
                placeholder="جستجو بر اساس شناسه کاربر، IP یا مسیر..."
                value={filters.q}
                hiddenParams={{
                    event: filters.event,
                    from: filters.from,
                    to: filters.to,
                    user_id: filters.user_id
                        ? String(filters.user_id)
                        : null,
                }}
                filters={
                    <>
                        <AdminFilterBar
                            basePath="/admin/security-events"
                            options={eventOptions}
                            currentStatus={filters.event}
                            searchQuery={filters.q}
                            paramKey="event"
                            extraParams={{
                                from: filters.from,
                                to: filters.to,
                                user_id: filters.user_id
                                    ? String(filters.user_id)
                                    : null,
                            }}
                            label="نوع رویداد"
                        />
                        <form
                            onSubmit={handleDateSubmit}
                            className="flex flex-col gap-2 rounded-xl bg-surface p-3 ring-1 ring-purple/10"
                        >
                            <span className="text-xs font-medium text-muted">
                                بازه زمانی و کاربر
                            </span>
                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <input
                                    type="date"
                                    value={from}
                                    onChange={(event) =>
                                        setFrom(event.target.value)
                                    }
                                    className="h-10 rounded-xl border border-[#e8e0f0] bg-surface px-3 text-sm text-text shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-purple/30"
                                    aria-label="از تاریخ"
                                />
                                <input
                                    type="date"
                                    value={to}
                                    onChange={(event) =>
                                        setTo(event.target.value)
                                    }
                                    className="h-10 rounded-xl border border-[#e8e0f0] bg-surface px-3 text-sm text-text shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-purple/30"
                                    aria-label="تا تاریخ"
                                />
                                <input
                                    type="text"
                                    inputMode="numeric"
                                    value={userId}
                                    onChange={(event) =>
                                        setUserId(event.target.value)
                                    }
                                    placeholder="شناسه کاربر"
                                    className="h-10 rounded-xl border border-[#e8e0f0] bg-surface px-3 text-sm text-text shadow-xs outline-none placeholder:text-muted focus-visible:ring-[3px] focus-visible:ring-purple/30"
                                    aria-label="شناسه کاربر"
                                />
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <AdminButton
                                    type="submit"
                                    size="sm"
                                    adminVariant="brand"
                                >
                                    اعمال فیلتر
                                </AdminButton>
                                {hasAdvancedFilters ? (
                                    <AdminButton
                                        asChild
                                        size="sm"
                                        adminVariant="outline"
                                    >
                                        <Link
                                            href={buildFilterHref(filters, {
                                                from: null,
                                                to: null,
                                                user_id: null,
                                            })}
                                            preserveState
                                        >
                                            پاک کردن بازه
                                        </Link>
                                    </AdminButton>
                                ) : null}
                            </div>
                        </form>
                    </>
                }
            />

            <div className="flex flex-col gap-3">
                <SecurityEventsList
                    events={events}
                    isSearchActive={Boolean(
                        filters.q ||
                            filters.event ||
                            filters.from ||
                            filters.to ||
                            filters.user_id,
                    )}
                />
            </div>
        </>
    );
}
