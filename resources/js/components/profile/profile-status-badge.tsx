import type { ProfileStatusTone } from '@/lib/profile-data';
import { cn } from '@/lib/utils';

const toneClassNames: Record<ProfileStatusTone, string> = {
    success: 'bg-green-soft text-green ring-green/15',
    warning: 'bg-gold-soft text-gold ring-gold/20',
    neutral: 'bg-purple-soft text-muted ring-purple/10',
};

type ProfileStatusBadgeProps = {
    tone: ProfileStatusTone;
    children: string;
    className?: string;
};

export function ProfileStatusBadge({
    tone,
    children,
    className,
}: ProfileStatusBadgeProps) {
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
