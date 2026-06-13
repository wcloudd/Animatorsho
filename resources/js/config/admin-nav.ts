import type { LucideIcon } from 'lucide-react';
import {
    CalendarClock,
    CreditCard,
    Headphones,
    KeyRound,
    LayoutDashboard,
    MessageSquare,
    Package,
    Settings,
    Shield,
    ShoppingBag,
    Smartphone,
    UserPlus,
} from 'lucide-react';

export type AdminNavLinkItem = {
    href: string;
    label: string;
    match: string;
    exact?: boolean;
    icon: LucideIcon;
};

export type AdminNavDisabledItem = {
    label: string;
    icon: LucideIcon;
    comingSoon: true;
};

export type AdminNavItem = AdminNavLinkItem | AdminNavDisabledItem;

export type AdminNavGroup = {
    key: string;
    label: string;
    items: AdminNavItem[];
};

export function isAdminNavLinkItem(
    item: AdminNavItem,
): item is AdminNavLinkItem {
    return !('comingSoon' in item);
}

export const adminNavGroups: AdminNavGroup[] = [
    {
        key: 'dashboard',
        label: 'داشبورد',
        items: [
            {
                href: '/admin',
                label: 'داشبورد',
                match: '/admin',
                exact: true,
                icon: LayoutDashboard,
            },
        ],
    },
    {
        key: 'finance',
        label: 'مالی و فروش',
        items: [
            {
                href: '/admin/orders',
                label: 'سفارش‌ها',
                match: '/admin/orders',
                icon: ShoppingBag,
            },
            {
                href: '/admin/payments',
                label: 'پرداخت‌ها',
                match: '/admin/payments',
                icon: CreditCard,
            },
            {
                href: '/admin/installments',
                label: 'پیگیری اقساط',
                match: '/admin/installments',
                icon: CalendarClock,
            },
            {
                href: '/admin#section-finance',
                label: 'گزارش مالی',
                match: '/admin',
                exact: true,
                icon: CreditCard,
            },
        ],
    },
    {
        key: 'users',
        label: 'کاربران و دسترسی‌ها',
        items: [
            {
                href: '/admin/manual-enrollments',
                label: 'کاربران و دسترسی‌ها',
                match: '/admin/manual-enrollments',
                icon: UserPlus,
            },
            {
                href: '/admin/licenses',
                label: 'لایسنس‌ها',
                match: '/admin/licenses',
                icon: KeyRound,
            },
        ],
    },
    {
        key: 'course',
        label: 'دوره و محتوا',
        items: [
            {
                href: '/admin/packages',
                label: 'بسته‌ها',
                match: '/admin/packages',
                icon: Package,
            },
            {
                label: 'آپدیت‌های دوره',
                icon: Package,
                comingSoon: true,
            },
            {
                label: 'کتابخانه / منابع',
                icon: Package,
                comingSoon: true,
            },
        ],
    },
    {
        key: 'communications',
        label: 'ارتباطات',
        items: [
            {
                href: '/admin/support',
                label: 'پشتیبانی',
                match: '/admin/support',
                icon: Headphones,
            },
            {
                href: '/admin/consultations',
                label: 'مشاوره‌ها',
                match: '/admin/consultations',
                icon: MessageSquare,
            },
            {
                label: 'تمرین‌ها / پیام استاد',
                icon: MessageSquare,
                comingSoon: true,
            },
        ],
    },
    {
        key: 'security',
        label: 'امنیت و سیستم',
        items: [
            {
                href: '/admin/security-events',
                label: 'رویدادهای امنیتی',
                match: '/admin/security-events',
                icon: Shield,
            },
            {
                href: '/admin/sms',
                label: 'پیامک',
                match: '/admin/sms',
                icon: Smartphone,
            },
        ],
    },
    {
        key: 'settings',
        label: 'تنظیمات',
        items: [
            {
                href: '/admin/site-settings',
                label: 'تنظیمات سایت',
                match: '/admin/site-settings',
                icon: Settings,
            },
        ],
    },
];

export const adminNavGroupLabels = adminNavGroups.map((group) => group.label);

export const adminNavLinkHrefs = adminNavGroups.flatMap((group) =>
    group.items.filter(isAdminNavLinkItem).map((item) => item.href),
);

export function isAdminNavLinkActive(
    url: string,
    item: AdminNavLinkItem,
): boolean {
    if (item.href === '/admin#section-finance') {
        return url === '/admin' || url === '/admin/';
    }

    return item.exact
        ? url === item.match || url === `${item.match}/`
        : url.startsWith(item.match);
}

export function isAdminNavGroupActive(
    url: string,
    group: AdminNavGroup,
): boolean {
    return group.items.some(
        (item) => isAdminNavLinkItem(item) && isAdminNavLinkActive(url, item),
    );
}
