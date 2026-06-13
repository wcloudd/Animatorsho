import { Head, Link } from '@inertiajs/react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import type {
    AdminCourseResourceDetectedFile,
    AdminCourseResourceListItem,
    AdminPaginated,
} from '@/types/admin';

type PageProps = {
    resources: AdminPaginated<AdminCourseResourceListItem>;
    detectedFiles: AdminCourseResourceDetectedFile[];
};

export default function AdminCourseResourcesIndex({
    resources,
    detectedFiles,
}: PageProps) {
    return (
        <>
            <Head title="منابع تمرین" />
            <AdminPageHeader
                title="منابع تمرین"
                description="فایل‌های کتابخانه به‌صورت خودکار شناسایی می‌شوند؛ اینجا می‌توانید عنوان و وضعیت نمایش را تنظیم کنید."
                actions={
                    <AdminButton asChild size="sm">
                        <Link href="/admin/course-resources/create">
                            افزودن اطلاعات
                        </Link>
                    </AdminButton>
                }
            />

            {detectedFiles.length > 0 ? (
                <div className="mb-5 flex flex-col gap-3">
                    <h2 className="text-sm font-bold text-text">
                        فایل‌های شناسایی‌شده بدون اطلاعات
                    </h2>
                    {detectedFiles.map((item) => (
                        <AdminCommerceCard
                            key={item.filePath}
                            title={item.title}
                            subtitle={item.filePath}
                            badge={{
                                label: 'خودکار',
                                tone: 'neutral',
                            }}
                            headerAction={
                                <AdminButton asChild size="sm" adminVariant="outline">
                                    <Link href={item.createUrl}>
                                        افزودن اطلاعات
                                    </Link>
                                </AdminButton>
                            }
                        >
                            <AdminInfoGrid>
                                <AdminDetailRow
                                    label="دسته"
                                    value={item.libraryCategoryLabel}
                                />
                                <AdminDetailRow
                                    label="نوع"
                                    value={item.typeLabel}
                                />
                            </AdminInfoGrid>
                        </AdminCommerceCard>
                    ))}
                </div>
            ) : null}

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
                                label="منبع"
                                value={item.sourceLabel}
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
                {resources.data.length === 0 && detectedFiles.length === 0 ? (
                    <AdminEmptyState message="هنوز منبعی ثبت نشده است. فایل‌ها را در پوشه‌های کتابخانه قرار دهید." />
                ) : null}
            </div>
            <AdminPagination paginator={resources} />
        </>
    );
}
