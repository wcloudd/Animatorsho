import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type AuthSecondaryActionCardProps = {
    href: string;
    label: string;
    icon?: LucideIcon;
    className?: string;
    alignEnd?: boolean;
    tabIndex?: number;
    'data-test'?: string;
};

export function AuthSecondaryActionCard({
    href,
    label,
    icon: Icon,
    className,
    alignEnd = false,
    tabIndex,
    'data-test': dataTest,
}: AuthSecondaryActionCardProps) {
    return (
        <Link
            href={href}
            tabIndex={tabIndex}
            data-test={dataTest}
            className={cn(
                'flex h-12 w-full items-center gap-2 rounded-2xl bg-purple-soft/50 text-sm font-bold text-text shadow-xs ring-1 ring-border transition-colors hover:bg-purple-soft/80',
                alignEnd ? 'justify-start px-4' : 'justify-center',
                className,
            )}
        >
            {Icon ? (
                <Icon className="size-4 shrink-0 text-muted" aria-hidden="true" />
            ) : null}
            <span>{label}</span>
        </Link>
    );
}
