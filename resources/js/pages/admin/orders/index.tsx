import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { AdminActionRow } from '@/components/admin/admin-action-row';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminConfirmAction } from '@/components/admin/admin-confirm-action';
import { AdminDetailBadgeRow } from '@/components/admin/admin-detail-badge-row';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminFilterBar } from '@/components/admin/admin-filter-bar';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminMetaDetails } from '@/components/admin/admin-meta-details';
import { AdminOrderCustomerEdit } from '@/components/admin/admin-order-customer-edit';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { AdminSearchBar } from '@/components/admin/admin-search-bar';
import { formatAdminDate } from '@/lib/format-admin-date';
import type {
    AdminOrderListItem,
    AdminPaginated,
    AdminStatusOption,
} from '@/types/admin';

type PageProps = {
    orders: AdminPaginated<AdminOrderListItem>;
    filters: { status: string | null; q: string | null };
    statusOptions: AdminStatusOption[];
};

function orderNeedsAttention(order: AdminOrderListItem): boolean {
    return order.canMarkPaid || order.canCancel;
}

export default function AdminOrdersIndex({
    orders,
    filters,
    statusOptions,
}: PageProps) {
    const [confirmKey, setConfirmKey] = useState<string | number | null>(null);

    return (
        <>
            <Head title="مدیریت سفارش‌ها" />
            <AdminPageHeader
                title="سفارش‌ها"
                description="پیگیری عملیاتی سفارش‌ها، وضعیت پرداخت و دسترسی لایسنس"
            />
            <AdminSearchBar
                basePath="/admin/orders"
                placeholder="جستجو بر اساس شماره سفارش، نام، موبایل..."
                value={filters.q}
                hiddenParams={{ status: filters.status }}
                filters={
                    <AdminFilterBar
                        basePath="/admin/orders"
                        options={statusOptions}
                        currentStatus={filters.status}
                        searchQuery={filters.q}
                        label="فیلتر وضعیت"
                    />
                }
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
                        highlight={orderNeedsAttention(order)}
                    >
                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="نام مشتری"
                                value={order.customerName}
                                truncateValue
                            />
                            <AdminDetailRow
                                label="موبایل مشتری"
                                value={order.customerMobile}
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
                                label="نوع پرداخت"
                                value={order.paymentType}
                            />
                            <AdminDetailRow
                                label="روش پرداخت"
                                value={order.latestPaymentMethod}
                            />
                            {order.externalSourceLabel ? (
                                <AdminDetailRow
                                    label="منبع خارجی"
                                    value={order.externalSourceLabel}
                                />
                            ) : null}
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
                                    <AdminConfirmAction
                                        actionKey={`mark-paid-${order.id}`}
                                        activeKey={confirmKey}
                                        onActivate={setConfirmKey}
                                        onCancel={() => setConfirmKey(null)}
                                        triggerLabel="علامت پرداخت‌شده"
                                        confirmLabel="تأیید پرداخت"
                                        message="وضعیت سفارش به پرداخت‌شده تغییر می‌کند."
                                        href={`/admin/orders/${order.id}/mark-paid`}
                                        triggerVariant="success"
                                        confirmVariant="success"
                                    />
                                ) : null}
                                {order.canCancel ? (
                                    <AdminConfirmAction
                                        actionKey={`cancel-${order.id}`}
                                        activeKey={confirmKey}
                                        onActivate={setConfirmKey}
                                        onCancel={() => setConfirmKey(null)}
                                        triggerLabel="لغو سفارش"
                                        confirmLabel="تأیید لغو"
                                        message="سفارش لغو می‌شود."
                                        href={`/admin/orders/${order.id}/cancel`}
                                    />
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
                    <AdminEmptyState
                        message="هنوز سفارشی ثبت نشده است."
                        isSearchActive={Boolean(filters.q)}
                    />
                ) : null}
            </div>
            <AdminPagination paginator={orders} />
        </>
    );
}
