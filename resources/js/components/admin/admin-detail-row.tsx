import { cn } from '@/lib/utils';

type AdminDetailRowProps = {
    label: string;
    value: string | null | undefined;
    valueClassName?: string;
    truncateValue?: boolean;
};

export function AdminDetailRow({
    label,
    value,
    valueClassName,
    truncateValue = false,
}: AdminDetailRowProps) {
    const display = value && value.trim() !== '' ? value : '—';

    return (
        <div className="flex justify-between gap-3 border-b border-purple/5 py-1.5 text-sm last:border-b-0">
            <dt className="shrink-0 text-muted">{label}</dt>
            <dd
                className={cn(
                    'min-w-0 text-left font-medium',
                    truncateValue && 'truncate',
                    !truncateValue && 'break-words',
                    valueClassName,
                )}
            >
                {display}
            </dd>
        </div>
    );
}
