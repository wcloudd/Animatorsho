import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminMetaDetails } from '@/components/admin/admin-meta-details';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { formatAdminDate } from '@/lib/format-admin-date';
import type { AdminPaginated, AdminSecurityEventItem } from '@/types/admin';

type SecurityEventsListProps = {
    events: AdminPaginated<AdminSecurityEventItem>;
    isSearchActive?: boolean;
};

export function SecurityEventsList({
    events,
    isSearchActive = false,
}: SecurityEventsListProps) {
    if (events.data.length === 0) {
        return (
            <AdminEmptyState
                message="هنوز رویداد امنیتی ثبت نشده است."
                isSearchActive={isSearchActive}
            />
        );
    }

    return (
        <>
            {events.data.map((item) => (
                <AdminCommerceCard
                    key={item.id}
                    title={item.event}
                    subtitle={
                        item.route
                            ? `${item.route}${item.method ? ` · ${item.method}` : ''}`
                            : (item.ip ?? '—')
                    }
                    badge={{
                        label: item.event,
                        tone: item.eventTone,
                    }}
                >
                    <AdminInfoGrid>
                        <AdminDetailRow
                            label="زمان وقوع"
                            value={formatAdminDate(item.occurredAt)}
                        />
                        <AdminDetailRow label="کاربر" value={item.userLabel} />
                        <AdminDetailRow label="IP" value={item.ip ?? '—'} />
                        <AdminDetailRow
                            label="مرورگر"
                            value={item.userAgent ?? '—'}
                        />
                    </AdminInfoGrid>
                    {item.metaItems.length > 0 ? (
                        <AdminMetaDetails title="جزئیات رویداد">
                            <AdminInfoGrid>
                                {item.metaItems.map((metaItem) => (
                                    <AdminDetailRow
                                        key={metaItem.key}
                                        label={metaItem.label}
                                        value={metaItem.value}
                                    />
                                ))}
                            </AdminInfoGrid>
                        </AdminMetaDetails>
                    ) : null}
                </AdminCommerceCard>
            ))}
            <AdminPagination paginator={events} />
        </>
    );
}
