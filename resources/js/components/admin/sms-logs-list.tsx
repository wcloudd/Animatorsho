import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminMetaDetails } from '@/components/admin/admin-meta-details';
import { AdminPagination } from '@/components/admin/admin-pagination';
import { formatAdminDate } from '@/lib/format-admin-date';
import type { AdminPaginated, AdminSmsLogItem } from '@/types/admin';

type SmsLogsListProps = {
    logs: AdminPaginated<AdminSmsLogItem>;
    isSearchActive?: boolean;
};

export function SmsLogsList({ logs, isSearchActive = false }: SmsLogsListProps) {
    if (logs.data.length === 0) {
        return (
            <AdminEmptyState
                message="هنوز پیامکی ثبت نشده است."
                isSearchActive={isSearchActive}
            />
        );
    }

    return (
        <>
            {logs.data.map((log) => (
                <AdminCommerceCard
                    key={log.id}
                    title={log.type}
                    subtitle={log.mobile ?? '—'}
                    badge={{
                        label: log.status,
                        tone: log.statusTone,
                    }}
                >
                    <AdminInfoGrid>
                        <AdminDetailRow label="درایور" value={log.provider} />
                        <AdminDetailRow
                            label="زمان ثبت"
                            value={formatAdminDate(log.createdAt)}
                        />
                        <AdminDetailRow
                            label="زمان ارسال"
                            value={formatAdminDate(log.sentAt)}
                        />
                    </AdminInfoGrid>
                    <p className="rounded-xl bg-bg px-3 py-2 text-sm text-text ring-1 ring-border/70">
                        {log.messagePreview}
                    </p>
                    {log.meta ? (
                        <AdminMetaDetails title="جزئیات فنی">
                            <pre className="overflow-x-auto rounded-xl bg-bg p-3 text-xs text-muted ring-1 ring-border/70">
                                {log.meta}
                            </pre>
                        </AdminMetaDetails>
                    ) : null}
                </AdminCommerceCard>
            ))}
            <AdminPagination paginator={logs} />
        </>
    );
}
