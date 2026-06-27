import { Head, Link } from '@inertiajs/react';
import { useAdminListFocus } from '@/hooks/use-admin-list-focus';
import { AdminCallout } from '@/components/admin/admin-callout';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailBadgeRow } from '@/components/admin/admin-detail-badge-row';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminFilterBar } from '@/components/admin/admin-filter-bar';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminInstallmentReviewPanel } from '@/components/admin/admin-installment-review-panel';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { AdminSearchBar } from '@/components/admin/admin-search-bar';
import { AdminTextLink } from '@/components/admin/admin-text-link';
import { formatAdminDate } from '@/lib/format-admin-date';
import { formatTomanPrice } from '@/lib/format-toman';
import type {
    AdminInstallmentListItem,
    AdminPaginated,
    AdminPaymentListItem,
    AdminStatusOption,
} from '@/types/admin';

type PageProps = {
    installments: AdminPaginated<AdminInstallmentListItem>;
    filters: { status: string | null; q: string | null; focus: number | null };
    statusOptions: AdminStatusOption[];
};

function installmentNeedsAttention(item: AdminInstallmentListItem): boolean {
    return item.canApprove || item.canReject;
}

function toReviewPayment(
    item: AdminInstallmentListItem,
): AdminPaymentListItem {
    return {
        id: item.paymentId ?? 0,
        orderNumber: item.orderNumber,
        userName: item.userName,
        userEmail: item.userEmail,
        customerName: item.customerName,
        customerMobile: item.customerMobile,
        packageTitle: item.packageTitle,
        method: 'خرید اقساطی',
        methodValue: 'installment',
        status: item.paymentStatus ?? '—',
        statusValue: item.paymentStatusValue ?? '',
        statusTone: item.paymentStatusTone ?? 'neutral',
        amountToman: item.amountToman,
        amountFormatted: item.amountFormatted,
        trackingCode: item.trackingCode,
        paidAt: item.installment?.downPaymentPaidAt ?? null,
        createdAt: item.createdAt,
        receiptUrl: item.receiptUrl,
        canApprove: item.canApprove,
        canReject: item.canReject,
        rejectionNote: item.rejectionNote,
        isInstallmentDownPaymentReceipt: item.isInstallmentDownPaymentReceipt,
        installmentRequestedTerm: item.installmentRequestedTerm,
        installmentNote: item.installmentNote,
        installment: item.installment,
        meta: null,
    };
}

export default function AdminInstallmentsIndex({
    installments,
    filters,
    statusOptions,
}: PageProps) {
    useAdminListFocus(filters.focus);

    return (
        <>
            <Head title="پیگیری اقساط" />
            <AdminPageHeader
                title="پیگیری اقساط"
                description="درخواست‌های خرید اقساطی، پیش‌پرداخت و وضعیت بررسی"
            />
            <AdminSearchBar
                basePath="/admin/installments"
                placeholder="جستجو بر اساس شماره سفارش، موبایل، کد پیگیری..."
                value={filters.q}
                hiddenParams={{ status: filters.status }}
                filters={
                    <AdminFilterBar
                        basePath="/admin/installments"
                        options={statusOptions}
                        currentStatus={filters.status}
                        searchQuery={filters.q}
                        label="فیلتر وضعیت"
                    />
                }
            />
            <div className="flex flex-col gap-3">
                {installments.data.map((item) => (
                    <AdminCommerceCard
                        key={item.id}
                        itemId={item.id}
                        title={item.orderNumber}
                        subtitle={item.packageTitle}
                        badge={{
                            label: item.orderStatus,
                            tone: item.orderStatusTone,
                        }}
                        highlight={installmentNeedsAttention(item)}
                        focused={filters.focus === item.id}
                    >
                        {item.canApprove || item.canReject ? (
                            <AdminInstallmentReviewPanel
                                payment={toReviewPayment(item)}
                            />
                        ) : null}

                        {item.rejectionNote ? (
                            <AdminCallout title="دلیل رد" variant="error">
                                {item.rejectionNote}
                            </AdminCallout>
                        ) : null}

                        <AdminInfoGrid>
                            <AdminDetailBadgeRow
                                label="وضعیت پرداخت"
                                value={item.paymentStatus}
                                tone={item.paymentStatusTone}
                            />
                            {item.installmentRequestedTerm ? (
                                <AdminDetailRow
                                    label="مدت اقساط"
                                    value={item.installmentRequestedTerm}
                                />
                            ) : null}
                            <AdminDetailRow
                                label="نام مشتری"
                                value={item.customerName}
                                truncateValue
                            />
                            <AdminDetailRow
                                label="موبایل مشتری"
                                value={item.customerMobile}
                            />
                            {item.installment?.cashPriceToman !== null &&
                            item.installment?.cashPriceToman !== undefined ? (
                                <AdminDetailRow
                                    label="قیمت نقدی"
                                    value={formatTomanPrice(
                                        item.installment.cashPriceToman,
                                    )}
                                />
                            ) : null}
                            {item.installment?.installmentTotalToman !==
                            null &&
                            item.installment?.installmentTotalToman !==
                                undefined ? (
                                <AdminDetailRow
                                    label="مبلغ کل اقساطی"
                                    value={formatTomanPrice(
                                        item.installment.installmentTotalToman,
                                    )}
                                />
                            ) : null}
                            {item.installment?.downPaymentToman !== null &&
                            item.installment?.downPaymentToman !== undefined ? (
                                <AdminDetailRow
                                    label="پیش‌پرداخت"
                                    value={formatTomanPrice(
                                        item.installment.downPaymentToman,
                                    )}
                                    valueClassName="font-bold text-purple"
                                />
                            ) : null}
                            {item.installment?.remainingToman !== null &&
                            item.installment?.remainingToman !== undefined ? (
                                <AdminDetailRow
                                    label="باقی‌مانده"
                                    value={formatTomanPrice(
                                        item.installment.remainingToman,
                                    )}
                                />
                            ) : null}
                            {item.installment?.downPaymentCaptured ? (
                                <AdminDetailRow
                                    label="پیش‌پرداخت ثبت‌شده"
                                    value={
                                        item.installment.downPaymentRef ??
                                        item.trackingCode ??
                                        'بله'
                                    }
                                    truncateValue
                                />
                            ) : null}
                            {item.installmentNote ? (
                                <AdminDetailRow
                                    label="توضیح کاربر"
                                    value={item.installmentNote}
                                />
                            ) : null}
                            <AdminDetailRow
                                label="تاریخ ثبت"
                                value={formatAdminDate(item.createdAt)}
                            />
                        </AdminInfoGrid>

                        <div className="flex flex-wrap gap-3 border-t border-border/60 pt-3">
                            {item.paymentReviewHref ? (
                                <AdminTextLink href={item.paymentReviewHref}>
                                    مشاهده در پرداخت‌ها
                                </AdminTextLink>
                            ) : null}
                            <AdminTextLink href={item.orderHref}>
                                مشاهده سفارش
                            </AdminTextLink>
                            {item.canApprove || item.canReject ? (
                                <Link
                                    href={`/admin/installments?focus=${item.id}`}
                                    className="text-sm font-medium text-muted hover:text-purple"
                                >
                                    تمرکز روی این درخواست
                                </Link>
                            ) : null}
                        </div>
                    </AdminCommerceCard>
                ))}
                {installments.data.length === 0 ? (
                    <AdminEmptyState
                        message="هنوز درخواست اقساطی ثبت نشده است."
                        isSearchActive={Boolean(filters.q || filters.status)}
                    />
                ) : null}
            </div>
            <AdminPagination paginator={installments} />
        </>
    );
}
