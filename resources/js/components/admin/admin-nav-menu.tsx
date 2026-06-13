import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    adminNavGroups,
    isAdminNavGroupActive,
    isAdminNavLinkActive,
    isAdminNavLinkItem,
    type AdminNavGroup,
    type AdminNavLinkItem,
} from '@/config/admin-nav';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
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
                'flex items-center gap-2.5 rounded-xl text-sm font-medium transition',
                compact ? 'px-2.5 py-2' : 'px-3 py-2.5',
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

function AdminNavDisabledItem({
    label,
    icon: Icon,
    compact = false,
}: {
    label: string;
    icon: AdminNavLinkItem['icon'];
    compact?: boolean;
}) {
    return (
        <div
            aria-disabled="true"
            className={cn(
                'flex items-center justify-between gap-2 rounded-xl text-sm text-muted/70',
                compact ? 'px-2.5 py-2' : 'px-3 py-2.5',
            )}
        >
            <span className="flex min-w-0 items-center gap-2.5">
                <Icon className="size-4 shrink-0 opacity-60" aria-hidden />
                <span className="truncate">{label}</span>
            </span>
            <span className="shrink-0 rounded-md bg-purple-soft px-1.5 py-0.5 text-[10px] font-medium text-purple/70 ring-1 ring-purple/10">
                به‌زودی
            </span>
        </div>
    );
}

function AdminNavGroupSection({
    group,
    url,
    onNavigate,
    defaultOpen,
    variant,
}: {
    group: AdminNavGroup;
    url: string;
    onNavigate?: () => void;
    defaultOpen: boolean;
    variant: 'mobile' | 'desktop';
}) {
    const [open, setOpen] = useState(defaultOpen);
    const isGroupActive = isAdminNavGroupActive(url, group);
    const compact = variant === 'desktop';

    useEffect(() => {
        if (defaultOpen) {
            setOpen(true);
        }
    }, [defaultOpen]);

    if (group.items.length === 1 && isAdminNavLinkItem(group.items[0])) {
        return (
            <AdminNavLink
                item={group.items[0]}
                url={url}
                onNavigate={onNavigate}
                compact={compact}
            />
        );
    }

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <CollapsibleTrigger
                className={cn(
                    'flex w-full items-center justify-between gap-2 rounded-xl text-sm font-semibold transition',
                    compact ? 'px-2.5 py-2' : 'px-3 py-2.5',
                    isGroupActive
                        ? 'bg-purple-soft text-purple ring-1 ring-purple/15'
                        : 'text-text hover:bg-purple-soft/60',
                )}
                aria-expanded={open}
            >
                <span>{group.label}</span>
                <ChevronDown
                    className={cn(
                        'size-4 shrink-0 text-muted transition-transform',
                        open && 'rotate-180',
                    )}
                />
            </CollapsibleTrigger>
            <CollapsibleContent className="flex flex-col gap-0.5 pt-1 pr-1">
                {group.items.map((item) =>
                    isAdminNavLinkItem(item) ? (
                        <AdminNavLink
                            key={`${group.key}-${item.href}`}
                            item={item}
                            url={url}
                            onNavigate={onNavigate}
                            compact={compact}
                        />
                    ) : (
                        <AdminNavDisabledItem
                            key={`${group.key}-${item.label}`}
                            label={item.label}
                            icon={item.icon}
                            compact={compact}
                        />
                    ),
                )}
            </CollapsibleContent>
        </Collapsible>
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
                    ? 'flex flex-col gap-1 p-3'
                    : 'hidden lg:grid lg:grid-cols-2 lg:gap-x-4 lg:gap-y-2 xl:grid-cols-3',
            )}
        >
            {adminNavGroups.map((group) => (
                <AdminNavGroupSection
                    key={group.key}
                    group={group}
                    url={url}
                    onNavigate={onNavigate}
                    defaultOpen={isAdminNavGroupActive(url, group)}
                    variant={variant}
                />
            ))}
        </nav>
    );
}
