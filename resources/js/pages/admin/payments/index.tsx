import { Head } from '@inertiajs/react';
import { useAdminListFocus } from '@/hooks/use-admin-list-focus';
import { AdminCallout } from '@/components/admin/admin-callout';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminFilterBar } from '@/components/admin/admin-filter-bar';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminMetaDetails } from '@/components/admin/admin-meta-details';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { AdminInstallmentReviewPanel } from '@/components/admin/admin-installment-review-panel';
import { AdminPaymentReviewPanel } from '@/components/admin/admin-payment-review-panel';
import { AdminSearchBar } from '@/components/admin/admin-search-bar';
import { formatAdminDate } from '@/lib/format-admin-date';
import type {
    AdminPaginated,
    AdminPaymentListItem,
    AdminStatusOption,
} from '@/types/admin';

type PageProps = {
    payments: AdminPaginated<AdminPaymentListItem>;
    filters: { status: string | null; q: string | null; focus: number | null };
    statusOptions: AdminStatusOption[];
};

function paymentNeedsAttention(payment: AdminPaymentListItem): boolean {
    return payment.canApprove || payment.canReject;
}

export default function AdminPaymentsIndex({
    payments,
    filters,
    statusOptions,
}: PageProps) {
    useAdminListFocus(filters.focus);

    return (
        <>
            <Head title="مدیریت پرداخت‌ها" />
            <AdminPageHeader
                title="پرداخت‌ها"
                description="بررسی مالی، کارت‌به‌کارت و جزئیات تراکنش"
            />
            <AdminSearchBar
                basePath="/admin/payments"
                placeholder="جستجو بر اساس شماره سفارش، کد پیگیری، موبایل..."
                value={filters.q}
                hiddenParams={{ status: filters.status }}
                filters={
                    <AdminFilterBar
                        basePath="/admin/payments"
                        options={statusOptions}
                        currentStatus={filters.status}
                        searchQuery={filters.q}
                        label="فیلتر وضعیت"
                    />
                }
            />
            <div className="flex flex-col gap-3">
                {payments.data.map((payment) => (
                    <AdminCommerceCard
                        key={payment.id}
                        itemId={payment.id}
                        title={payment.orderNumber}
                        subtitle={payment.packageTitle}
                        badge={{
                            label: payment.status,
                            tone: payment.statusTone,
                        }}
                        highlight={paymentNeedsAttention(payment)}
                        focused={filters.focus === payment.id}
                    >
                        {payment.methodValue === 'installment' &&
                        (payment.canApprove || payment.canReject) ? (
                            <AdminInstallmentReviewPanel payment={payment} />
                        ) : null}

                        {payment.methodValue === 'card_to_card' &&
                        (payment.canApprove || payment.canReject) ? (
                            <AdminPaymentReviewPanel payment={payment} />
                        ) : null}

                        {payment.rejectionNote ? (
                            <AdminCallout title="دلیل رد" variant="error">
                                {payment.rejectionNote}
                            </AdminCallout>
                        ) : null}

                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="نام مشتری"
                                value={payment.customerName}
                                truncateValue
                            />
                            <AdminDetailRow
                                label="موبایل مشتری"
                                value={payment.customerMobile}
                            />
                            <AdminDetailRow
                                label="مبلغ"
                                value={payment.amountFormatted}
                                valueClassName="font-bold text-purple"
                            />
                            <AdminDetailRow
                                label="روش پرداخت"
                                value={payment.method}
                            />
                            {payment.installmentRequestedTerm ? (
                                <AdminDetailRow
                                    label="مدت اقساط"
                                    value={payment.installmentRequestedTerm}
                                />
                            ) : null}
                            {payment.installmentNote ? (
                                <AdminDetailRow
                                    label="توضیح کاربر"
                                    value={payment.installmentNote}
                                />
                            ) : null}
                            <AdminDetailRow
                                label="کد پیگیری"
                                value={payment.trackingCode}
                                truncateValue
                            />
                            <AdminDetailRow
                                label="تاریخ پرداخت"
                                value={formatAdminDate(payment.paidAt)}
                            />
                            <AdminDetailRow
                                label="تاریخ ثبت"
                                value={formatAdminDate(payment.createdAt)}
                            />
                        </AdminInfoGrid>

                        {payment.meta ? (
                            <AdminMetaDetails title="جزئیات فنی پرداخت">
                                <AdminInfoGrid className="mb-2 rounded-xl bg-bg p-3 ring-1 ring-border/70">
                                    <AdminDetailRow
                                        label="کاربر"
                                        value={payment.userName}
                                    />
                                    <AdminDetailRow
                                        label="شناسه پرداخت"
                                        value={String(payment.id)}
                                    />
                                </AdminInfoGrid>
                                <pre className="overflow-x-auto rounded-xl bg-purple-soft/50 p-3 text-xs text-muted">
                                    {payment.meta}
                                </pre>
                            </AdminMetaDetails>
                        ) : null}
                    </AdminCommerceCard>
                ))}
                {payments.data.length === 0 ? (
                    <AdminEmptyState
                        message="هنوز پرداختی ثبت نشده است."
                        isSearchActive={Boolean(filters.q)}
                    />
                ) : null}
            </div>
            <AdminPagination paginator={payments} />
        </>
    );
}
