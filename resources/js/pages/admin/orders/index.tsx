import { Head, Link } from '@inertiajs/react';
import { AdminActionRow } from '@/components/admin/admin-action-row';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailBadgeRow } from '@/components/admin/admin-detail-badge-row';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminFilterBar } from '@/components/admin/admin-filter-bar';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminMetaDetails } from '@/components/admin/admin-meta-details';
import { AdminOrderCustomerEdit } from '@/components/admin/admin-order-customer-edit';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { formatAdminDate } from '@/lib/format-admin-date';
import type {
    AdminOrderListItem,
    AdminPaginated,
    AdminStatusOption,
} from '@/types/admin';

type PageProps = {
    orders: AdminPaginated<AdminOrderListItem>;
    filters: { status: string | null };
    statusOptions: AdminStatusOption[];
};

export default function AdminOrdersIndex({
    orders,
    filters,
    statusOptions,
}: PageProps) {
    return (
        <>
            <Head title="مدیریت سفارش‌ها" />
            <AdminPageHeader
                title="سفارش‌ها"
                description="پیگیری عملیاتی سفارش‌ها، وضعیت پرداخت و دسترسی لایسنس"
            />
            <AdminFilterBar
                basePath="/admin/orders"
                options={statusOptions}
                currentStatus={filters.status}
            />
            <div className="flex flex-col gap-3">
                {orders.data.map((order) => (
                    <AdminCommerceCard
                        key={order.id}
                        title={order.orderNumber}
                        subtitle={order.packageTitle}
                        badge={{
                            label: order.status,
                            tone: order.statusTone,
                        }}
                    >
                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="نام مشتری"
                                value={order.customerName}
                            />
                            <AdminDetailRow
                                label="موبایل مشتری"
                                value={order.customerMobile}
                            />
                            <AdminDetailRow
                                label="نوع پرداخت"
                                value={order.paymentType}
                            />
                            <AdminDetailRow
                                label="مبلغ نهایی"
                                value={order.finalAmountFormatted}
                                valueClassName="font-bold text-purple"
                            />
                            <AdminDetailBadgeRow
                                label="وضعیت پرداخت"
                                value={order.latestPaymentStatus}
                                tone={order.latestPaymentStatusTone}
                            />
                            <AdminDetailRow
                                label="روش پرداخت"
                                value={order.latestPaymentMethod}
                            />
                            <AdminDetailBadgeRow
                                label="وضعیت لایسنس"
                                value={order.licenseStatus}
                                tone={order.licenseStatusTone}
                            />
                            <AdminDetailRow
                                label="تاریخ ثبت"
                                value={formatAdminDate(order.createdAt)}
                            />
                        </AdminInfoGrid>

                        <AdminActionRow>
                            <div className="flex flex-wrap gap-2">
                                <AdminOrderCustomerEdit order={order} />
                                {order.canMarkPaid ? (
                                    <AdminButton asChild size="sm" adminVariant="success">
                                        <Link
                                            href={`/admin/orders/${order.id}/mark-paid`}
                                            method="post"
                                            as="button"
                                            preserveScroll
                                        >
                                            علامت پرداخت‌شده
                                        </Link>
                                    </AdminButton>
                                ) : null}
                                {order.canCancel ? (
                                    <AdminButton
                                        asChild
                                        size="sm"
                                        adminVariant="dangerOutline"
                                    >
                                        <Link
                                            href={`/admin/orders/${order.id}/cancel`}
                                            method="post"
                                            as="button"
                                            preserveScroll
                                        >
                                            لغو سفارش
                                        </Link>
                                    </AdminButton>
                                ) : null}
                            </div>
                        </AdminActionRow>

                        <AdminMetaDetails title="جزئیات بیشتر">
                            <AdminInfoGrid className="rounded-xl bg-bg p-3 ring-1 ring-border/70">
                                <AdminDetailRow
                                    label="کاربر"
                                    value={order.userName}
                                />
                                <AdminDetailRow
                                    label="ایمیل"
                                    value={order.userEmail}
                                />
                                <AdminDetailRow
                                    label="مبلغ اولیه"
                                    value={order.amountFormatted}
                                />
                                <AdminDetailRow
                                    label="شناسه سفارش"
                                    value={String(order.id)}
                                />
                            </AdminInfoGrid>
                        </AdminMetaDetails>
                    </AdminCommerceCard>
                ))}
                {orders.data.length === 0 ? (
                    <p className="text-center text-sm text-muted">
                        سفارشی یافت نشد.
                    </p>
                ) : null}
            </div>
            <AdminPagination paginator={orders} />
        </>
    );
}
