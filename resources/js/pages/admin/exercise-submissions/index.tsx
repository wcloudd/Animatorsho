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
    AdminExerciseSubmissionListItem,
    AdminPaginated,
    AdminStatusOption,
} from '@/types/admin';

type PageProps = {
    submissions: AdminPaginated<AdminExerciseSubmissionListItem>;
    filters: { status: string | null; q: string | null };
    statusOptions: AdminStatusOption[];
};

export default function AdminExerciseSubmissionsIndex({
    submissions,
    filters,
    statusOptions,
}: PageProps) {
    const basePath = '/admin/exercise-submissions';

    return (
        <>
            <Head title="تمرین‌های ارسالی" />
            <AdminPageHeader
                title="تمرین‌های ارسالی"
                description="بررسی تمرین‌های ارسالی هنرجویان"
            />
            <AdminSearchBar
                basePath={basePath}
                placeholder="جستجو بر اساس عنوان، نام، موبایل..."
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
                {submissions.data.map((item) => (
                    <AdminCommerceCard
                        key={item.id}
                        title={item.title}
                        subtitle={`${item.studentName} · ${item.studentMobile ?? '—'}`}
                        badge={{
                            label: item.status,
                            tone: item.statusTone,
                        }}
                        headerAction={
                            <AdminButton asChild size="sm" adminVariant="outline">
                                <Link href={item.reviewUrl}>بررسی</Link>
                            </AdminButton>
                        }
                    >
                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="نام هنرجو"
                                value={item.studentName}
                            />
                            <AdminDetailRow
                                label="موبایل"
                                value={item.studentMobile ?? '—'}
                            />
                            <AdminDetailRow
                                label="تاریخ ارسال"
                                value={item.submittedAtLabel}
                            />
                            <AdminDetailRow
                                label="فایل‌های پیوست"
                                value={`${item.attachmentCount} عدد`}
                            />
                            {item.reviewedAtLabel !== '—' ? (
                                <AdminDetailRow
                                    label="تاریخ بررسی"
                                    value={item.reviewedAtLabel}
                                />
                            ) : null}
                        </AdminInfoGrid>
                    </AdminCommerceCard>
                ))}
                {submissions.data.length === 0 ? (
                    <AdminEmptyState
                        message="هنوز تمرینی ارسال نشده است."
                        isSearchActive={Boolean(filters.q || filters.status)}
                    />
                ) : null}
            </div>
            <AdminPagination paginator={submissions} />
        </>
    );
}
