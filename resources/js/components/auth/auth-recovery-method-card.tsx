import { cn } from '@/lib/utils';

type AuthRecoveryMethodCardProps = {
    label: string;
    selected: boolean;
    onSelect: () => void;
    'data-test'?: string;
};

export function AuthRecoveryMethodCard({
    label,
    selected,
    onSelect,
    'data-test': dataTest,
}: AuthRecoveryMethodCardProps) {
    return (
        <button
            type="button"
            onClick={onSelect}
            data-test={dataTest}
            className={cn(
                'flex min-h-12 flex-1 items-center justify-center rounded-2xl px-3 py-3 text-sm font-bold transition-all',
                selected
                    ? 'bg-surface text-purple shadow-soft ring-2 ring-purple/30'
                    : 'bg-purple-soft/40 text-muted ring-1 ring-border hover:bg-purple-soft/70',
            )}
        >
            {label}
        </button>
    );
}
