import { BRAND_OG_IMAGE_FALLBACK_PATH } from '@/lib/brand-assets';
import type { SeoHeadProps } from '@/types/seo';

/** Fallback OG image until dedicated social asset is uploaded. */
export const DEFAULT_OG_IMAGE_PATH = BRAND_OG_IMAGE_FALLBACK_PATH;

export const PUBLIC_PAGE_SEO = {
    home: {
        title: 'انیماتورشو — آموزش ساخت انیمیشن',
        description:
            'دوره انیماتورشو برای یادگیری ساخت انیمیشن از صفر تا اولین خروجی؛ با مسیر ساده، فصل‌بندی شفاف و پشتیبانی.',
    },
    consultation: {
        title: 'مشاوره رایگان انیماتورشو',
        description:
            'درخواست مشاوره رایگان برای انتخاب مسیر مناسب یادگیری انیمیشن و آشنایی با دوره انیماتورشو.',
    },
    checkout: {
        title: 'ثبت‌نام دوره انیماتورشو',
        description:
            'انتخاب بسته دوره انیماتورشو، مشاهده گزینه‌های خرید و شروع ثبت‌نام در دوره آموزش انیمیشن.',
    },
    login: {
        title: 'ورود به انیماتورشو',
        description:
            'ورود به حساب کاربری انیماتورشو برای دسترسی به سفارش‌ها، لایسنس SpotPlayer و پشتیبانی دوره.',
    },
    register: {
        title: 'ثبت‌نام در انیماتورشو',
        description:
            'ساخت حساب کاربری در انیماتورشو برای ثبت‌نام دوره، مدیریت سفارش‌ها و پیگیری مسیر یادگیری.',
    },
    forgotPassword: {
        title: 'بازیابی رمز عبور انیماتورشو',
        description:
            'بازیابی رمز عبور حساب انیماتورشو با شماره موبایل یا ایمیل.',
    },
} as const satisfies Record<string, Pick<SeoHeadProps, 'title' | 'description'>>;

export function absoluteUrl(appUrl: string, path: string): string {
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    const normalizedPath = path.startsWith('/') ? path : `/${path}`;

    if (normalizedPath === '/') {
        return appUrl;
    }

    return `${appUrl}${normalizedPath}`;
}

export function canonicalFromPath(appUrl: string, path: string): string {
    return absoluteUrl(appUrl, path);
}

export function defaultOpenGraph(
    appUrl: string,
    {
        title,
        description,
        path,
        imagePath = DEFAULT_OG_IMAGE_PATH,
        type = 'website',
    }: {
        title: string;
        description: string;
        path: string;
        imagePath?: string;
        type?: string;
    },
): NonNullable<SeoHeadProps['openGraph']> {
    return {
        title,
        description,
        url: canonicalFromPath(appUrl, path),
        type,
        image: absoluteUrl(appUrl, imagePath),
    };
}
