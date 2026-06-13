import { Head, Link } from '@inertiajs/react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import type {
    AdminCourseResourceListItem,
    AdminPaginated,
} from '@/types/admin';

type PageProps = {
    resources: AdminPaginated<AdminCourseResourceListItem>;
};

export default function AdminCourseResourcesIndex({ resources }: PageProps) {
    return (
        <>
            <Head title="منابع تمرین" />
            <AdminPageHeader
                title="منابع تمرین"
                description="مدیریت فایل‌ها و رفرنس‌های کتابخانه پنل هنرجو"
                actions={
                    <AdminButton asChild size="sm">
                        <Link href="/admin/course-resources/create">
                            منبع جدید
                        </Link>
                    </AdminButton>
                }
            />
            <div className="flex flex-col gap-3">
                {resources.data.map((item) => (
                    <AdminCommerceCard
                        key={item.id}
                        title={item.title}
                        subtitle={item.description ?? 'بدون توضیح'}
                        badge={{
                            label: item.statusLabel,
                            tone: item.statusTone,
                        }}
                        headerAction={
                            <AdminButton asChild size="sm" adminVariant="outline">
                                <Link href={item.editUrl}>ویرایش</Link>
                            </AdminButton>
                        }
                    >
                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="نوع"
                                value={item.typeLabel}
                            />
                            <AdminDetailRow
                                label="دسته"
                                value={item.categoryLabel}
                            />
                            <AdminDetailRow
                                label="دسترسی"
                                value={item.accessScopeLabel}
                            />
                            <AdminDetailRow
                                label="تاریخ انتشار"
                                value={item.publishedAtLabel}
                            />
                            <AdminDetailRow
                                label="ترتیب نمایش"
                                value={String(item.displayOrder)}
                            />
                        </AdminInfoGrid>
                    </AdminCommerceCard>
                ))}
                {resources.data.length === 0 ? (
                    <AdminEmptyState message="هنوز منبعی ثبت نشده است." />
                ) : null}
            </div>
            <AdminPagination paginator={resources} />
        </>
    );
}
