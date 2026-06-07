import { Head, Link } from '@inertiajs/react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import type { AdminPackageListItem, AdminPaginated } from '@/types/admin';

type PageProps = {
    packages: AdminPaginated<AdminPackageListItem>;
};

export default function AdminPackagesIndex({ packages }: PageProps) {
    return (
        <>
            <Head title="مدیریت بسته‌ها" />
            <AdminPageHeader
                title="بسته‌های دوره"
                description="قیمت و وضعیت نمایش بسته‌ها را مدیریت کنید."
            />
            <div className="flex flex-col gap-3">
                {packages.data.map((pkg) => (
                    <AdminCommerceCard
                        key={pkg.id}
                        title={pkg.title}
                        subtitle={
                            <span className="font-mono text-xs">{pkg.slug}</span>
                        }
                        badge={{
                            label: pkg.isActive ? 'فعال' : 'غیرفعال',
                            tone: pkg.isActive ? 'success' : 'neutral',
                        }}
                        headerAction={
                            <AdminButton asChild size="sm" adminVariant="outline">
                                <Link href={`/admin/packages/${pkg.id}/edit`}>
                                    ویرایش
                                </Link>
                            </AdminButton>
                        }
                    >
                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="قیمت"
                                value={pkg.priceFormatted}
                                valueClassName="font-bold text-purple"
                            />
                            <AdminDetailRow
                                label="ترتیب نمایش"
                                value={String(pkg.displayOrder)}
                            />
                            <AdminDetailRow
                                label="سفارش‌های ثبت‌شده"
                                value={String(pkg.ordersCount)}
                            />
                        </AdminInfoGrid>
                    </AdminCommerceCard>
                ))}
                {packages.data.length === 0 ? (
                    <AdminEmptyState message="هنوز بسته‌ای تعریف نشده است." />
                ) : null}
            </div>
            <AdminPagination paginator={packages} />
        </>
    );
}
