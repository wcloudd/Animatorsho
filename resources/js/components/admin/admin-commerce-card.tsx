import type { ReactNode } from 'react';
import { surfaceCardClassName } from '@/components/page-container';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import type { ProfileStatusTone } from '@/lib/profile-data';
import { cn } from '@/lib/utils';

type AdminCommerceCardProps = {
    title: ReactNode;
    subtitle?: ReactNode;
    badge?: { label: string; tone: ProfileStatusTone };
    headerAction?: ReactNode;
    children?: ReactNode;
    footer?: ReactNode;
    className?: string;
};

export function AdminCommerceCard({
    title,
    subtitle,
    badge,
    headerAction,
    children,
    footer,
    className,
}: AdminCommerceCardProps) {
    return (
        <article
            className={cn(
                surfaceCardClassName,
                'flex flex-col gap-3',
                className,
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <h3 className="font-bold text-text">{title}</h3>
                    {subtitle ? (
                        <p className="mt-1 text-sm text-muted">{subtitle}</p>
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
