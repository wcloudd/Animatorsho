import type { LucideIcon } from 'lucide-react';
import {
    CircleAlert,
    CircleCheck,
    CircleX,
    Clock,
    ClipboardList,
} from 'lucide-react';

export type CheckoutResultStatus =
    | 'success'
    | 'failed'
    | 'manual-review'
    | 'installment-review'
    | 'payment-pending';

export type CheckoutResultVisualTone =
    | 'success'
    | 'failed'
    | 'gold'
    | 'purple'
    | 'neutral';

export type CheckoutResultContent = {
    status: CheckoutResultStatus | 'fallback';
    title: string;
    description: string;
    primaryCtaLabel: string;
    primaryCtaHref: string;
    secondaryCtaLabel: string;
    secondaryCtaHref: string;
    orderStatusLabel: string;
    visualTone: CheckoutResultVisualTone;
    icon: LucideIcon;
    statusBadgeLabel: string;
};

const CHECKOUT_RESULT_CONTENT: Record<
    CheckoutResultStatus,
    Omit<CheckoutResultContent, 'status'>
> = {
    success: {
        title: 'ثبت‌نام با موفقیت انجام شد',
        description:
            'پرداخت شما با موفقیت ثبت شد. بعد از فعال‌سازی دسترسی، اطلاعات دوره و لایسنس SpotPlayer از بخش پروفایل قابل مشاهده خواهد بود.',
        primaryCtaLabel: 'رفتن به پروفایل',
        primaryCtaHref: '/profile',
        secondaryCtaLabel: 'رفتن به صفحه انیماتورشو',
        secondaryCtaHref: '/',
        orderStatusLabel: 'پرداخت موفق',
        visualTone: 'success',
        icon: CircleCheck,
        statusBadgeLabel: 'موفق',
    },
    failed: {
        title: 'پرداخت ناموفق بود',
        description:
            'پرداخت شما کامل نشد یا توسط درگاه تأیید نشد. می‌تونی دوباره تلاش کنی یا اگر مبلغی از حساب کم شده، با پشتیبانی در ارتباط باش.',
        primaryCtaLabel: 'تلاش دوباره',
        primaryCtaHref: '/checkout',
        secondaryCtaLabel: 'پشتیبانی',
        secondaryCtaHref: '/support',
        orderStatusLabel: 'پرداخت ناموفق',
        visualTone: 'failed',
        icon: CircleX,
        statusBadgeLabel: 'ناموفق',
    },
    'manual-review': {
        title: 'در انتظار بررسی پرداخت',
        description:
            'اطلاعات پرداخت کارت‌به‌کارت شما ثبت شده و بعد از بررسی توسط پشتیبانی، وضعیت سفارش مشخص می‌شود.',
        primaryCtaLabel: 'مشاهده وضعیت در پروفایل',
        primaryCtaHref: '/profile',
        secondaryCtaLabel: 'پیام به پشتیبانی',
        secondaryCtaHref: '/support',
        orderStatusLabel: 'در انتظار بررسی پشتیبانی',
        visualTone: 'gold',
        icon: Clock,
        statusBadgeLabel: 'در انتظار بررسی',
    },
    'installment-review': {
        title: 'درخواست اقساطی ثبت شد',
        description:
            'درخواست خرید اقساطی شما ثبت شده و پشتیبانی برای هماهنگی با شما تماس می‌گیرد.',
        primaryCtaLabel: 'مشاهده وضعیت در پروفایل',
        primaryCtaHref: '/profile',
        secondaryCtaLabel: 'پیام به پشتیبانی',
        secondaryCtaHref: '/support',
        orderStatusLabel: 'در انتظار بررسی اقساط',
        visualTone: 'purple',
        icon: ClipboardList,
        statusBadgeLabel: 'بررسی اقساط',
    },
    'payment-pending': {
        title: 'سفارش ثبت شد',
        description:
            'سفارش شما ثبت شد، اما اتصال به درگاه پرداخت هنوز فعال نشده است. در مرحله بعد، پرداخت آنلاین از طریق درگاه بانکی فعال خواهد شد.',
        primaryCtaLabel: 'مشاهده وضعیت در پروفایل',
        primaryCtaHref: '/profile',
        secondaryCtaLabel: 'پشتیبانی',
        secondaryCtaHref: '/support',
        orderStatusLabel: 'در انتظار پرداخت آنلاین',
        visualTone: 'gold',
        icon: Clock,
        statusBadgeLabel: 'ثبت سفارش',
    },
};

export const CHECKOUT_RESULT_FALLBACK: CheckoutResultContent = {
    status: 'fallback',
    title: 'وضعیت سفارش مشخص نیست',
    description:
        'برای ادامه، به صفحه خرید برگرد یا از پشتیبانی کمک بگیر.',
    primaryCtaLabel: 'بازگشت به خرید',
    primaryCtaHref: '/checkout',
    secondaryCtaLabel: 'پشتیبانی',
    secondaryCtaHref: '/support',
    orderStatusLabel: 'نامشخص',
    visualTone: 'neutral',
    icon: CircleAlert,
    statusBadgeLabel: 'نامشخص',
};

export const CHECKOUT_RESULT_PRODUCT_LABEL = 'دوره جامع انیماتورشو' as const;

export const CHECKOUT_RESULT_LICENSE_NOTE =
    'بعد از تأیید در پروفایل نمایش داده می‌شود' as const;

const VALID_STATUSES = new Set<string>([
    'success',
    'failed',
    'manual-review',
    'installment-review',
    'payment-pending',
]);

export function isCheckoutResultStatus(
    value: string | null,
): value is CheckoutResultStatus {
    return value !== null && VALID_STATUSES.has(value);
}

export function getCheckoutResultContent(
    rawStatus: string | null,
): CheckoutResultContent {
    if (!isCheckoutResultStatus(rawStatus)) {
        return CHECKOUT_RESULT_FALLBACK;
    }

    const content = CHECKOUT_RESULT_CONTENT[rawStatus];

    return {
        status: rawStatus,
        ...content,
    };
}

export const RESULT_VISUAL_STYLES: Record<
    CheckoutResultVisualTone,
    {
        cardClassName: string;
        iconWrapClassName: string;
        badgeClassName: string;
    }
> = {
    success: {
        cardClassName: 'bg-green-soft',
        iconWrapClassName: 'bg-green text-white',
        badgeClassName: 'bg-surface text-green ring-green/20',
    },
    failed: {
        cardClassName: 'bg-red/10',
        iconWrapClassName: 'bg-red text-white',
        badgeClassName: 'bg-surface text-red ring-red/20',
    },
    gold: {
        cardClassName: 'bg-gold-soft',
        iconWrapClassName: 'bg-gold text-white',
        badgeClassName: 'bg-surface text-gold ring-gold/20',
    },
    purple: {
        cardClassName: 'bg-purple-soft',
        iconWrapClassName: 'bg-purple text-white',
        badgeClassName: 'bg-surface text-purple ring-purple/20',
    },
    neutral: {
        cardClassName: 'bg-surface',
        iconWrapClassName: 'bg-muted/20 text-muted',
        badgeClassName: 'bg-surface text-muted ring-border',
    },
};
