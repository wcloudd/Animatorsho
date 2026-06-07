import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import type { ProfileStatusTone } from '@/lib/profile-data';

type AdminDetailBadgeRowProps = {
    label: string;
    value: string | null | undefined;
    tone: ProfileStatusTone | null | undefined;
};

export function AdminDetailBadgeRow({
    label,
    value,
    tone,
}: AdminDetailBadgeRowProps) {
    const hasValue = value && value.trim() !== '';

    return (
        <div className="flex justify-between gap-3 text-sm">
            <dt className="shrink-0 text-muted">{label}</dt>
            <dd className="text-left">
                {hasValue && tone ? (
                    <AdminStatusBadge tone={tone}>{value}</AdminStatusBadge>
                ) : (
                    <span className="font-medium">—</span>
                )}
            </dd>
        </div>
    );
}
