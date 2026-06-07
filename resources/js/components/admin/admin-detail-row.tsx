type AdminDetailRowProps = {
    label: string;
    value: string | null | undefined;
    valueClassName?: string;
};

export function AdminDetailRow({
    label,
    value,
    valueClassName,
}: AdminDetailRowProps) {
    const display = value && value.trim() !== '' ? value : '—';

    return (
        <div className="flex justify-between gap-3 text-sm">
            <dt className="shrink-0 text-muted">{label}</dt>
            <dd className={`text-left font-medium ${valueClassName ?? ''}`}>
                {display}
            </dd>
        </div>
    );
}
