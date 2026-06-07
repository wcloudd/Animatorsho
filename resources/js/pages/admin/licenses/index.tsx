import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { AdminActionRow } from '@/components/admin/admin-action-row';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminConfirmAction } from '@/components/admin/admin-confirm-action';
import { AdminDetailBadgeRow } from '@/components/admin/admin-detail-badge-row';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminLicenseActivationPanel } from '@/components/admin/admin-license-activation-panel';
import { AdminMetaDetails } from '@/components/admin/admin-meta-details';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { AdminSearchBar } from '@/components/admin/admin-search-bar';
import { formatAdminDate } from '@/lib/format-admin-date';
import type { AdminLicenseListItem, AdminPaginated } from '@/types/admin';

type PageProps = {
    licenses: AdminPaginated<AdminLicenseListItem>;
    filters: { q: string | null };
};

function licenseSubtitle(license: AdminLicenseListItem): string {
    const customer =
        license.orderCustomerName?.trim() || license.userName || '—';
    const order = license.orderNumber ?? 'بدون سفارش';

    return `${customer} — ${order}`;
}

function shouldShowLicenseKey(license: AdminLicenseListItem): boolean {
    if (!license.licenseKey?.trim()) {
        return false;
    }

    return !license.canActivate;
}

export default function AdminLicensesIndex({ licenses, filters }: PageProps) {
    const [confirmKey, setConfirmKey] = useState<string | number | null>(null);

    return (
        <>
            <Head title="مدیریت لایسنس‌ها" />
            <AdminPageHeader
                title="لایسنس‌های SpotPlayer"
                description="فعال‌سازی، دسترسی و مدیریت کلید لایسنس"
            />
            <AdminSearchBar
                basePath="/admin/licenses"
                placeholder="جستجو بر اساس کلید لایسنس، شماره سفارش، موبایل..."
                value={filters.q}
            />
            <div className="flex flex-col gap-3">
                {licenses.data.map((license) => (
                    <AdminCommerceCard
                        key={license.id}
                        title={license.packageTitle}
                        subtitle={licenseSubtitle(license)}
                        badge={{
                            label: license.status,
                            tone: license.statusTone,
                        }}
                    >
                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="نام مشتری"
                                value={license.orderCustomerName}
                            />
                            <AdminDetailRow
                                label="موبایل مشتری"
                                value={license.orderCustomerMobile}
                            />
                            <AdminDetailBadgeRow
                                label="وضعیت سفارش"
                                value={license.orderStatus}
                                tone={license.orderStatusTone}
                            />
                            <AdminDetailBadgeRow
                                label="وضعیت پرداخت"
                                value={license.latestPaymentStatus}
                                tone={license.latestPaymentStatusTone}
                            />
                            <AdminDetailRow
                                label="تاریخ فعال‌سازی"
                                value={formatAdminDate(license.activatedAt)}
                            />
                            <AdminDetailRow
                                label="روش فعال‌سازی"
                                value={license.provisionedViaLabel}
                            />
                        </AdminInfoGrid>

                        {license.apiFailureSummary ? (
                            <div className="rounded-xl bg-gold-soft px-3 py-2 ring-1 ring-gold/20">
                                <p className="text-xs font-medium text-muted">
                                    خطای آخرین تلاش API
                                </p>
                                <p className="mt-1 text-sm text-text">
                                    {license.apiFailureSummary}
                                </p>
                            </div>
                        ) : null}

                        {shouldShowLicenseKey(license) ? (
                            <div className="rounded-xl bg-purple-soft/50 px-3 py-2 ring-1 ring-purple/15">
                                <p className="text-xs font-medium text-muted">
                                    کلید لایسنس
                                </p>
                                <p
                                    className="mt-1 break-all font-mono text-sm font-medium text-text"
                                    dir="ltr"
                                >
                                    {license.licenseKey}
                                </p>
                            </div>
                        ) : null}

                        {license.canActivate ? (
                            <AdminLicenseActivationPanel license={license} />
                        ) : null}

                        {license.canRetryProvision ? (
                            <AdminActionRow>
                                <AdminConfirmAction
                                    actionKey={`retry-${license.id}`}
                                    activeKey={confirmKey}
                                    onActivate={setConfirmKey}
                                    onCancel={() => setConfirmKey(null)}
                                    triggerLabel="تلاش مجدد SpotPlayer API"
                                    confirmLabel="تأیید تلاش مجدد"
                                    href={`/admin/licenses/${license.id}/retry-provision`}
                                    triggerVariant="outline"
                                    confirmVariant="brand"
                                />
                            </AdminActionRow>
                        ) : null}

                        {license.canRevoke ? (
                            <AdminActionRow>
                                <AdminConfirmAction
                                    actionKey={`revoke-${license.id}`}
                                    activeKey={confirmKey}
                                    onActivate={setConfirmKey}
                                    onCancel={() => setConfirmKey(null)}
                                    triggerLabel="لغو لایسنس"
                                    confirmLabel="تأیید لغو"
                                    href={`/admin/licenses/${license.id}/revoke`}
                                />
                            </AdminActionRow>
                        ) : null}

                        <AdminMetaDetails title="جزئیات بیشتر">
                            <AdminInfoGrid className="rounded-xl bg-bg p-3 ring-1 ring-border/70">
                                <AdminDetailRow
                                    label="کاربر"
                                    value={license.userName}
                                />
                                <AdminDetailRow
                                    label="ایمیل"
                                    value={license.userEmail}
                                />
                                <AdminDetailRow
                                    label="شماره سفارش"
                                    value={license.orderNumber}
                                />
                                <AdminDetailRow
                                    label="شناسه لایسنس"
                                    value={String(license.id)}
                                />
                                <AdminDetailRow
                                    label="آخرین تلاش API"
                                    value={formatAdminDate(
                                        license.apiTechnicalDetails
                                            .lastApiAttemptAt,
                                    )}
                                />
                                <AdminDetailRow
                                    label="کد HTTP"
                                    value={
                                        license.apiTechnicalDetails
                                            .lastApiHttpStatus !== null
                                            ? String(
                                                  license.apiTechnicalDetails
                                                      .lastApiHttpStatus,
                                              )
                                            : null
                                    }
                                />
                                <AdminDetailRow
                                    label="شناسه SpotPlayer"
                                    value={
                                        license.apiTechnicalDetails
                                            .spotplayerLicenseId
                                    }
                                />
                                {license.apiTechnicalDetails.spotplayerErrorMessage ? (
                                    <AdminDetailRow
                                        label="پیام SpotPlayer"
                                        value={
                                            license.apiTechnicalDetails
                                                .spotplayerErrorMessage
                                        }
                                    />
                                ) : null}
                                {license.apiTechnicalDetails
                                    .spotplayerResponseKeys.length > 0 ? (
                                    <AdminDetailRow
                                        label="کلیدهای پاسخ"
                                        value={license.apiTechnicalDetails.spotplayerResponseKeys.join(
                                            ', ',
                                        )}
                                    />
                                ) : null}
                                {license.apiTechnicalDetails
                                    .spotplayerResponsePreview ? (
                                    <AdminDetailRow
                                        label="پیش‌نمایش پاسخ"
                                        value={
                                            license.apiTechnicalDetails
                                                .spotplayerResponsePreview
                                        }
                                        valueClassName="break-all font-mono text-xs"
                                    />
                                ) : null}
                                {license.apiTechnicalDetails.lastApiError ? (
                                    <AdminDetailRow
                                        label="پیام فنی API"
                                        value={
                                            license.apiTechnicalDetails
                                                .lastApiError
                                        }
                                    />
                                ) : null}
                            </AdminInfoGrid>
                        </AdminMetaDetails>
                    </AdminCommerceCard>
                ))}
                {licenses.data.length === 0 ? (
                    <AdminEmptyState
                        message="لایسنسی یافت نشد."
                        isSearchActive={Boolean(filters.q)}
                    />
                ) : null}
            </div>
            <AdminPagination paginator={licenses} />
        </>
    );
}
