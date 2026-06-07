import { Head } from '@inertiajs/react';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminConsultationUpdatePanel } from '@/components/admin/admin-consultation-update-panel';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminFilterBar } from '@/components/admin/admin-filter-bar';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { AdminSearchBar } from '@/components/admin/admin-search-bar';
import type {
    AdminConsultationListItem,
    AdminPaginated,
    AdminStatusOption,
} from '@/types/admin';

type PageProps = {
    consultations: AdminPaginated<AdminConsultationListItem>;
    filters: { status: string | null; q: string | null };
    statusOptions: AdminStatusOption[];
};

export default function AdminConsultationsIndex({
    consultations,
    filters,
    statusOptions,
}: PageProps) {
    const basePath = '/admin/consultations';

    return (
        <>
            <Head title="مشاوره‌های رایگان" />
            <AdminPageHeader
                title="مشاوره‌ها"
                description="پیگیری درخواست‌های مشاوره رایگان"
            />
            <AdminSearchBar
                basePath={basePath}
                placeholder="جستجو بر اساس نام، موبایل، متن..."
                value={filters.q}
                hiddenParams={{ status: filters.status }}
                filters={
                    <AdminFilterBar
                        basePath={basePath}
                        options={statusOptions}
                        currentStatus={filters.status}
                        searchQuery={filters.q}
                        label="وضعیت"
                    />
                }
            />
            <div className="flex flex-col gap-3">
                {consultations.data.map((item) => (
                    <AdminCommerceCard
                        key={item.id}
                        title={item.name}
                        subtitle={item.mobile}
                        badge={{
                            label: item.status,
                            tone: item.statusTone,
                        }}
                    >
                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="موبایل"
                                value={item.mobile}
                            />
                            <AdminDetailRow
                                label="سطح"
                                value={item.level ?? '—'}
                            />
                            <AdminDetailRow
                                label="علاقه‌مند به"
                                value={item.interest ?? '—'}
                                truncateValue
                            />
                            {item.age ? (
                                <AdminDetailRow label="سن" value={item.age} />
                            ) : null}
                            <AdminDetailRow
                                label="توضیح کاربر"
                                value={item.note ?? '—'}
                                truncateValue
                            />
                            <AdminDetailRow
                                label="تاریخ ثبت"
                                value={item.createdAt ?? '—'}
                            />
                            <AdminDetailRow
                                label="آخرین به‌روزرسانی"
                                value={item.updatedAt ?? '—'}
                            />
                        </AdminInfoGrid>
                        <AdminConsultationUpdatePanel
                            consultation={item}
                            statusOptions={statusOptions}
                        />
                    </AdminCommerceCard>
                ))}
                {consultations.data.length === 0 ? (
                    <AdminEmptyState
                        message="درخواست مشاوره‌ای ثبت نشده است."
                        isSearchActive={Boolean(filters.q || filters.status)}
                    />
                ) : null}
            </div>
            <AdminPagination paginator={consultations} />
        </>
    );
}
