import { Head, Link } from '@inertiajs/react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
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
                            <div>
                                <dt className="text-xs text-muted">قیمت</dt>
                                <dd className="mt-0.5 text-base font-bold text-text">
                                    {pkg.priceFormatted}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted">
                                    ترتیب نمایش
                                </dt>
                                <dd className="mt-0.5 text-base font-bold text-text">
                                    {pkg.displayOrder}
                                </dd>
                            </div>
                        </AdminInfoGrid>
                        <p className="text-xs text-muted">
                            {pkg.ordersCount} سفارش ثبت‌شده
                        </p>
                    </AdminCommerceCard>
                ))}
                {packages.data.length === 0 ? (
                    <p className="text-center text-sm text-muted">
                        بسته‌ای یافت نشد.
                    </p>
                ) : null}
            </div>
            <AdminPagination paginator={packages} />
        </>
    );
}
