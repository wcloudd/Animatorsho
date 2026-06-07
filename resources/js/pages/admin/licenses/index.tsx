import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { AdminActionRow } from '@/components/admin/admin-action-row';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailBadgeRow } from '@/components/admin/admin-detail-badge-row';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminLicenseActivationPanel } from '@/components/admin/admin-license-activation-panel';
import { AdminMetaDetails } from '@/components/admin/admin-meta-details';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { formatAdminDate } from '@/lib/format-admin-date';
import type { AdminLicenseListItem, AdminPaginated } from '@/types/admin';

type PageProps = {
    licenses: AdminPaginated<AdminLicenseListItem>;
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

export default function AdminLicensesIndex({ licenses }: PageProps) {
    const [revokeConfirmId, setRevokeConfirmId] = useState<number | null>(
        null,
    );

    return (
        <>
            <Head title="مدیریت لایسنس‌ها" />
            <AdminPageHeader
                title="لایسنس‌های SpotPlayer"
                description="فعال‌سازی، دسترسی و مدیریت کلید لایسنس"
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
                        </AdminInfoGrid>

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

                        {license.canRevoke ? (
                            <AdminActionRow>
                                {revokeConfirmId === license.id ? (
                                    <div className="flex flex-wrap gap-2">
                                        <AdminButton
                                            asChild
                                            size="sm"
                                            adminVariant="danger"
                                        >
                                            <Link
                                                href={`/admin/licenses/${license.id}/revoke`}
                                                method="post"
                                                as="button"
                                                preserveScroll
                                            >
                                                تأیید لغو
                                            </Link>
                                        </AdminButton>
                                        <AdminButton
                                            type="button"
                                            size="sm"
                                            adminVariant="outline"
                                            onClick={() =>
                                                setRevokeConfirmId(null)
                                            }
                                        >
                                            انصراف
                                        </AdminButton>
                                    </div>
                                ) : (
                                    <AdminButton
                                        type="button"
                                        size="sm"
                                        adminVariant="dangerOutline"
                                        onClick={() =>
                                            setRevokeConfirmId(license.id)
                                        }
                                    >
                                        لغو لایسنس
                                    </AdminButton>
                                )}
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
                            </AdminInfoGrid>
                        </AdminMetaDetails>
                    </AdminCommerceCard>
                ))}
                {licenses.data.length === 0 ? (
                    <p className="text-center text-sm text-muted">
                        لایسنسی یافت نشد.
                    </p>
                ) : null}
            </div>
            <AdminPagination paginator={licenses} />
        </>
    );
}
