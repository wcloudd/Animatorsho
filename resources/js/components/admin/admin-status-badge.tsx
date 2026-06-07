import type { ProfileStatusTone } from '@/lib/profile-data';
import { cn } from '@/lib/utils';

export type AdminStatusTone = ProfileStatusTone | 'danger';

const toneClassNames: Record<AdminStatusTone, string> = {
    success: 'bg-green-soft text-green ring-green/15',
    warning: 'bg-gold-soft text-gold ring-gold/20',
    neutral: 'bg-purple-soft text-muted ring-purple/10',
    danger: 'bg-red/10 text-red/75 ring-red/15',
};

type AdminStatusBadgeProps = {
    tone: AdminStatusTone;
    children: string;
    className?: string;
};

export function AdminStatusBadge({
    tone,
    children,
    className,
}: AdminStatusBadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center rounded-pill px-2.5 py-1 text-xs font-bold ring-1',
                toneClassNames[tone],
                className,
            )}
        >
            {children}
        </span>
    );
}
