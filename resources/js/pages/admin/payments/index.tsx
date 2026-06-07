import { Head } from '@inertiajs/react';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminFilterBar } from '@/components/admin/admin-filter-bar';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminMetaDetails } from '@/components/admin/admin-meta-details';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { AdminInstallmentReviewPanel } from '@/components/admin/admin-installment-review-panel';
import { AdminPaymentReviewPanel } from '@/components/admin/admin-payment-review-panel';
import { formatAdminDate } from '@/lib/format-admin-date';
import type {
    AdminPaginated,
    AdminPaymentListItem,
    AdminStatusOption,
} from '@/types/admin';

type PageProps = {
    payments: AdminPaginated<AdminPaymentListItem>;
    filters: { status: string | null };
    statusOptions: AdminStatusOption[];
};

export default function AdminPaymentsIndex({
    payments,
    filters,
    statusOptions,
}: PageProps) {
    return (
        <>
            <Head title="مدیریت پرداخت‌ها" />
            <AdminPageHeader
                title="پرداخت‌ها"
                description="بررسی مالی، کارت‌به‌کارت و جزئیات تراکنش"
            />
            <AdminFilterBar
                basePath="/admin/payments"
                options={statusOptions}
                currentStatus={filters.status}
            />
            <div className="flex flex-col gap-3">
                {payments.data.map((payment) => (
                    <AdminCommerceCard
                        key={payment.id}
                        title={payment.orderNumber}
                        subtitle={payment.packageTitle}
                        badge={{
                            label: payment.status,
                            tone: payment.statusTone,
                        }}
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
                            <div className="rounded-xl bg-red-soft/60 px-3 py-2 ring-1 ring-red/20">
                                <p className="text-xs font-medium text-muted">
                                    دلیل رد
                                </p>
                                <p className="mt-1 text-sm font-medium text-text">
                                    {payment.rejectionNote}
                                </p>
                            </div>
                        ) : null}

                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="نام مشتری"
                                value={payment.customerName}
                            />
                            <AdminDetailRow
                                label="موبایل مشتری"
                                value={payment.customerMobile}
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
                                label="مبلغ"
                                value={payment.amountFormatted}
                                valueClassName="font-bold text-purple"
                            />
                            <AdminDetailRow
                                label="کد پیگیری"
                                value={payment.trackingCode}
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
                    <p className="text-center text-sm text-muted">
                        پرداختی یافت نشد.
                    </p>
                ) : null}
            </div>
            <AdminPagination paginator={payments} />
        </>
    );
}
