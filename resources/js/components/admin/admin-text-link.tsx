import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type AdminTextLinkProps = ComponentProps<typeof Link> & {
    variant?: 'default' | 'subtle';
};

export function AdminTextLink({
    className,
    variant = 'default',
    children,
    ...props
}: AdminTextLinkProps) {
    return (
        <Link
            className={cn(
                'shrink-0 text-sm font-medium transition',
                variant === 'default'
                    ? 'text-purple underline-offset-2 hover:underline'
                    : 'text-muted hover:text-purple',
                className,
            )}
            {...props}
        >
            {children}
        </Link>
    );
}
