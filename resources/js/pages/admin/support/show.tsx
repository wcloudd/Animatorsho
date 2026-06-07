import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { AdminActionRow } from '@/components/admin/admin-action-row';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminConfirmAction } from '@/components/admin/admin-confirm-action';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminMetaDetails } from '@/components/admin/admin-meta-details';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import { surfaceCardClassName } from '@/components/page-container';
import { SupportTicketConversation } from '@/components/support/support-ticket-conversation';
import { SupportTicketMessageForm } from '@/components/support/support-ticket-message-form';
import { cn } from '@/lib/utils';
import type {
    AdminSupportLicenseContext,
    AdminSupportOrderContext,
    AdminSupportTicketDetail,
    AdminSupportTicketMessage,
} from '@/types/admin';

type PageProps = {
    ticket: AdminSupportTicketDetail;
    messages: AdminSupportTicketMessage[];
    recentOrders: AdminSupportOrderContext[];
    recentLicenses: AdminSupportLicenseContext[];
};

function ContextList({
    title,
    items,
    renderItem,
}: {
    title: string;
    items: Array<{ id: number }>;
    renderItem: (item: (typeof items)[number]) => ReactNode;
}) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-col gap-2">
            <AdminSectionTitle className="mb-0">{title}</AdminSectionTitle>
            <ul className="flex flex-col gap-2">{items.map(renderItem)}</ul>
        </div>
    );
}

export default function AdminSupportShow({
    ticket,
    messages,
    recentOrders,
    recentLicenses,
}: PageProps) {
    const [confirmKey, setConfirmKey] = useState<string | number | null>(null);

    return (
        <>
            <Head title={`پشتیبانی — ${ticket.subject}`} />
            <AdminPageHeader
                title={ticket.subject}
                description={`${ticket.userName} · ${ticket.category}`}
                actions={
                    <AdminButton asChild size="sm" adminVariant="outline">
                        <Link href="/admin/support">بازگشت به لیست</Link>
                    </AdminButton>
                }
            />

            <div
                className={cn(
                    surfaceCardClassName,
                    'mb-4 flex flex-col gap-4 p-4 sm:p-5',
                )}
            >
                <AdminInfoGrid>
                    <AdminDetailRow label="وضعیت" value={ticket.status} />
                    <AdminDetailRow
                        label="نام مشتری"
                        value={ticket.customerName}
                    />
                    <AdminDetailRow
                        label="موبایل"
                        value={ticket.customerMobile ?? '—'}
                    />
                    <AdminDetailRow
                        label="ایمیل کاربر"
                        value={ticket.userEmail}
                        truncateValue
                    />
                    <AdminDetailRow
                        label="تاریخ ثبت"
                        value={ticket.createdAt ?? '—'}
                    />
                    {ticket.closedAt ? (
                        <AdminDetailRow
                            label="تاریخ بسته‌شدن"
                            value={ticket.closedAt}
                        />
                    ) : null}
                </AdminInfoGrid>

                <AdminActionRow>
                    {ticket.isClosed ? (
                        <AdminConfirmAction
                            actionKey="reopen"
                            activeKey={confirmKey}
                            onActivate={setConfirmKey}
                            onCancel={() => setConfirmKey(null)}
                            triggerLabel="باز کردن تیکت"
                            confirmLabel="تأیید بازگشایی"
                            href={`/admin/support/${ticket.id}/reopen`}
                            triggerVariant="success"
                            confirmVariant="success"
                        />
                    ) : (
                        <AdminConfirmAction
                            actionKey="close"
                            activeKey={confirmKey}
                            onActivate={setConfirmKey}
                            onCancel={() => setConfirmKey(null)}
                            triggerLabel="بستن تیکت"
                            confirmLabel="تأیید بستن"
                            href={`/admin/support/${ticket.id}/close`}
                        />
                    )}
                </AdminActionRow>
            </div>

            <AdminMetaDetails title="اطلاعات تکمیلی">
                <div className="flex flex-col gap-4">
                    <ContextList
                        title="سفارش‌های اخیر"
                        items={recentOrders}
                        renderItem={(order) => (
                            <li
                                key={order.id}
                                className="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-bg px-3 py-2 ring-1 ring-border/70"
                            >
                                <span className="min-w-0 truncate text-sm text-text">
                                    {order.orderNumber} · {order.packageTitle}
                                </span>
                                <AdminStatusBadge tone={order.statusTone}>
                                    {order.status}
                                </AdminStatusBadge>
                            </li>
                        )}
                    />
                    <ContextList
                        title="لایسنس‌های اخیر"
                        items={recentLicenses}
                        renderItem={(license) => (
                            <li
                                key={license.id}
                                className="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-bg px-3 py-2 ring-1 ring-border/70"
                            >
                                <span className="min-w-0 truncate text-sm text-text">
                                    {license.packageTitle}
                                </span>
                                <AdminStatusBadge tone={license.statusTone}>
                                    {license.status}
                                </AdminStatusBadge>
                            </li>
                        )}
                    />
                </div>
            </AdminMetaDetails>

            <section
                className={cn(
                    surfaceCardClassName,
                    'my-4 p-4 sm:p-5',
                )}
            >
                <AdminSectionTitle className="mb-4">گفتگو</AdminSectionTitle>
                <SupportTicketConversation messages={messages} />
            </section>

            {!ticket.isClosed ? (
                <SupportTicketMessageForm
                    action={`/admin/support/${ticket.id}/messages`}
                    waitingForUserField
                />
            ) : (
                <p className="rounded-xl bg-purple-soft/40 px-4 py-3 text-sm text-muted ring-1 ring-purple/10">
                    این تیکت بسته شده است.
                </p>
            )}
        </>
    );
}
