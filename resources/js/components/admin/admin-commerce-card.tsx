import type { ReactNode } from 'react';
import { surfaceCardClassName } from '@/components/page-container';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import type { AdminStatusTone } from '@/components/admin/admin-status-badge';
import { cn } from '@/lib/utils';

type AdminCommerceCardProps = {
    title: ReactNode;
    subtitle?: ReactNode;
    badge?: { label: string; tone: AdminStatusTone };
    headerAction?: ReactNode;
    children?: ReactNode;
    footer?: ReactNode;
    className?: string;
    highlight?: boolean;
    highlightTone?: 'action' | 'error';
    itemId?: number;
    focused?: boolean;
};

export function AdminCommerceCard({
    title,
    subtitle,
    badge,
    headerAction,
    children,
    footer,
    className,
    highlight = false,
    highlightTone = 'action',
    itemId,
    focused = false,
}: AdminCommerceCardProps) {
    return (
        <article
            id={itemId ? `admin-item-${itemId}` : undefined}
            className={cn(
                surfaceCardClassName,
                'flex flex-col gap-3 p-4 sm:p-5',
                highlight &&
                    !focused &&
                    (highlightTone === 'error'
                        ? 'ring-red/25'
                        : 'ring-gold/35'),
                focused && 'ring-2 ring-purple/45',
                className,
            )}
        >
            <div className="flex items-start justify-between gap-3 border-b border-purple/8 pb-3">
                <div className="min-w-0 flex-1">
                    <h3 className="truncate font-bold text-text">{title}</h3>
                    {subtitle ? (
                        <p className="mt-1 truncate text-sm text-muted">
                            {subtitle}
                        </p>
                    ) : null}
                </div>
                <div className="flex shrink-0 items-start gap-2">
                    {badge ? (
                        <AdminStatusBadge tone={badge.tone}>
                            {badge.label}
                        </AdminStatusBadge>
                    ) : null}
                    {headerAction}
                </div>
            </div>
            {children}
            {footer}
        </article>
    );
}
