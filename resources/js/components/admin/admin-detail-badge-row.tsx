import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import type { AdminStatusTone } from '@/components/admin/admin-status-badge';
import { cn } from '@/lib/utils';

type AdminDetailBadgeRowProps = {
    label: string;
    value: string | null | undefined;
    tone: AdminStatusTone | null | undefined;
};

export function AdminDetailBadgeRow({
    label,
    value,
    tone,
}: AdminDetailBadgeRowProps) {
    const hasValue = value && value.trim() !== '';

    return (
        <div className="flex justify-between gap-3 border-b border-purple/5 py-1.5 text-sm last:border-b-0">
            <dt className="shrink-0 text-muted">{label}</dt>
            <dd className="min-w-0 text-left">
                {hasValue && tone ? (
                    <AdminStatusBadge tone={tone}>{value}</AdminStatusBadge>
                ) : (
                    <span className="font-medium">—</span>
                )}
            </dd>
        </div>
    );
}
