import { Head, Link } from '@inertiajs/react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import type {
    AdminCourseUpdateListItem,
    AdminPaginated,
} from '@/types/admin';

type PageProps = {
    updates: AdminPaginated<AdminCourseUpdateListItem>;
};

export default function AdminCourseUpdatesIndex({ updates }: PageProps) {
    return (
        <>
            <Head title="آپدیت‌های دوره" />
            <AdminPageHeader
                title="آپدیت‌های دوره"
                description="ایجاد و مدیریت اعلان‌های پنل هنرجو"
                actions={
                    <AdminButton asChild size="sm">
                        <Link href="/admin/course-updates/create">
                            آپدیت جدید
                        </Link>
                    </AdminButton>
                }
            />
            <div className="flex flex-col gap-3">
                {updates.data.map((item) => (
                    <AdminCommerceCard
                        key={item.id}
                        title={item.title}
                        subtitle={item.summary ?? 'بدون خلاصه'}
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
                                label="تم بصری"
                                value={item.visualThemeLabel}
                            />
                            <AdminDetailRow
                                label="تاریخ انتشار"
                                value={item.publishedAtLabel}
                            />
                            <AdminDetailRow
                                label="ترتیب نمایش"
                                value={String(item.displayOrder)}
                            />
                            <AdminDetailRow
                                label="سنجاق‌شده"
                                value={item.isPinned ? 'بله' : 'خیر'}
                            />
                        </AdminInfoGrid>
                    </AdminCommerceCard>
                ))}
                {updates.data.length === 0 ? (
                    <AdminEmptyState message="هنوز آپدیتی ثبت نشده است." />
                ) : null}
            </div>
            <AdminPagination paginator={updates} />
        </>
    );
}
