import { Link } from '@inertiajs/react';
import {
    adminNavGroups,
    isAdminNavLinkActive,
    type AdminNavLinkItem,
    type AdminNavGroup,
} from '@/config/admin-nav';
import { cn } from '@/lib/utils';

function AdminNavLink({
    item,
    url,
    onNavigate,
    compact = false,
}: {
    item: AdminNavLinkItem;
    url: string;
    onNavigate?: () => void;
    compact?: boolean;
}) {
    const isActive = isAdminNavLinkActive(url, item);
    const Icon = item.icon;

    return (
        <Link
            href={item.href}
            onClick={onNavigate}
            aria-current={isActive ? 'page' : undefined}
            className={cn(
                'flex items-center gap-2 rounded-xl text-sm font-medium transition',
                compact ? 'px-2 py-1.5' : 'px-3 py-2',
                isActive
                    ? 'bg-purple text-white shadow-xs ring-1 ring-purple/20'
                    : 'text-text hover:bg-purple-soft hover:text-purple',
            )}
        >
            <Icon className="size-4 shrink-0" aria-hidden />
            <span>{item.label}</span>
        </Link>
    );
}

function AdminNavGroupHeading({
    label,
    compact = false,
}: {
    label: string;
    compact?: boolean;
}) {
    return (
        <div className="flex flex-col gap-1.5">
            <div className="flex items-center gap-2.5">
                <span
                    className={cn(
                        'shrink-0 font-display font-bold text-purple',
                        compact ? 'text-[11px]' : 'text-xs',
                    )}
                >
                    {label}
                </span>
                <span
                    className="h-px flex-1 bg-purple/25"
                    aria-hidden
                />
            </div>
        </div>
    );
}

function AdminNavGroupBlock({
    group,
    url,
    onNavigate,
    variant,
}: {
    group: AdminNavGroup;
    url: string;
    onNavigate?: () => void;
    variant: 'mobile' | 'desktop';
}) {
    const compact = variant === 'desktop';
    const singleLink = group.items.length === 1 ? group.items[0] : null;

    if (singleLink !== null) {
        return (
            <div className={cn(compact ? 'min-w-0' : undefined)}>
                <AdminNavGroupHeading
                    label={group.label}
                    compact={compact}
                />
                <AdminNavLink
                    item={singleLink}
                    url={url}
                    onNavigate={onNavigate}
                    compact={compact}
                />
            </div>
        );
    }

    return (
        <div className={cn(compact ? 'min-w-0' : undefined)}>
            <AdminNavGroupHeading
                label={group.label}
                compact={compact}
            />
            <div
                className={cn(
                    'flex flex-col',
                    compact ? 'gap-0.5' : 'gap-0.5',
                )}
            >
                {group.items.map((item) => (
                    <AdminNavLink
                        key={item.href}
                        item={item}
                        url={url}
                        onNavigate={onNavigate}
                        compact={compact}
                    />
                ))}
            </div>
        </div>
    );
}

export function AdminNavMenu({
    url,
    onNavigate,
    variant,
}: {
    url: string;
    onNavigate?: () => void;
    variant: 'mobile' | 'desktop';
}) {
    return (
        <nav
            aria-label="منوی مدیریت"
            className={cn(
                variant === 'mobile'
                    ? 'flex flex-col gap-3 p-3'
                    : 'hidden lg:grid lg:grid-cols-4 lg:gap-x-4 lg:gap-y-2 xl:grid-cols-7',
            )}
        >
            {adminNavGroups.map((group) => (
                <AdminNavGroupBlock
                    key={group.key}
                    group={group}
                    url={url}
                    onNavigate={onNavigate}
                    variant={variant}
                />
            ))}
        </nav>
    );
}
