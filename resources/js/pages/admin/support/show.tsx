import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { AdminActionRow } from '@/components/admin/admin-action-row';
import { AdminConfirmAction } from '@/components/admin/admin-confirm-action';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminMetaDetails } from '@/components/admin/admin-meta-details';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import { SupportTicketConversation } from '@/components/support/support-ticket-conversation';
import { SupportTicketMessageForm } from '@/components/support/support-ticket-message-form';
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
            <h3 className="text-sm font-bold text-text">{title}</h3>
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
                    <Link
                        href="/admin/support"
                        className="text-sm text-purple hover:underline"
                    >
                        بازگشت به لیست
                    </Link>
                }
            />

            <div className="mb-4 rounded-[24px] bg-surface p-5 ring-1 ring-purple/10">
                <AdminInfoGrid>
                    <AdminDetailRow
                        label="وضعیت"
                        value={ticket.status}
                    />
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

                <AdminActionRow className="mt-4">
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
                                className="flex flex-wrap items-center justify-between gap-2 rounded-[16px] bg-bg px-3 py-2"
                            >
                                <span className="text-sm text-text">
                                    {order.orderNumber} · {order.packageTitle}
                                </span>
                                <ProfileStatusBadge tone={order.statusTone}>
                                    {order.status}
                                </ProfileStatusBadge>
                            </li>
                        )}
                    />
                    <ContextList
                        title="لایسنس‌های اخیر"
                        items={recentLicenses}
                        renderItem={(license) => (
                            <li
                                key={license.id}
                                className="flex flex-wrap items-center justify-between gap-2 rounded-[16px] bg-bg px-3 py-2"
                            >
                                <span className="text-sm text-text">
                                    {license.packageTitle}
                                </span>
                                <ProfileStatusBadge tone={license.statusTone}>
                                    {license.status}
                                </ProfileStatusBadge>
                            </li>
                        )}
                    />
                </div>
            </AdminMetaDetails>

            <section className="my-4 rounded-[24px] bg-surface p-5 ring-1 ring-purple/10">
                <h2 className="mb-4 text-base font-bold text-text">گفتگو</h2>
                <SupportTicketConversation messages={messages} />
            </section>

            {!ticket.isClosed ? (
                <SupportTicketMessageForm
                    action={`/admin/support/${ticket.id}/messages`}
                    waitingForUserField
                />
            ) : (
                <p className="text-sm text-muted">این تیکت بسته شده است.</p>
            )}
        </>
    );
}
