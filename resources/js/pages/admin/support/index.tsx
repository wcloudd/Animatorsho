import { Head, Link } from '@inertiajs/react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminFilterBar } from '@/components/admin/admin-filter-bar';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { AdminSearchBar } from '@/components/admin/admin-search-bar';
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
                filters={
                    <>
                        <AdminFilterBar
                            basePath={basePath}
                            options={statusOptions}
                            currentStatus={filters.status}
                            searchQuery={filters.q}
                            extraParams={{ category: filters.category }}
                            label="وضعیت"
                        />
                        <AdminFilterBar
                            basePath={basePath}
                            options={categoryOptions}
                            currentStatus={filters.category}
                            paramKey="category"
                            allLabel="همه دسته‌ها"
                            searchQuery={filters.q}
                            extraParams={{ status: filters.status }}
                            label="دسته‌بندی"
                        />
                    </>
                }
            />
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
                            <AdminButton asChild size="sm" adminVariant="outline">
                                <Link href={`/admin/support/${ticket.id}`}>
                                    مشاهده
                                </Link>
                            </AdminButton>
                        }
                    >
                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="نام مشتری"
                                value={ticket.customerName}
                                truncateValue
                            />
                            <AdminDetailRow
                                label="موبایل"
                                value={ticket.customerMobile ?? '—'}
                            />
                            <AdminDetailRow
                                label="ایمیل کاربر"
                                value={ticket.userEmail}
                                truncateValue
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
                        message="تیکت باز یا بسته‌ای ثبت نشده است."
                        isSearchActive={Boolean(filters.q)}
                    />
                ) : null}
            </div>
            <AdminPagination paginator={tickets} />
        </>
    );
}
