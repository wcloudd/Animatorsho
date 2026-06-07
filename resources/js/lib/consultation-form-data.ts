export type ConsultationFormOption = {
    value: string;
    label: string;
};

export type ConsultationIntroBadge = {
    id: string;
    label: string;
};

export const CONSULTATION_LEVEL_OPTIONS: ConsultationFormOption[] = [
    { value: 'beginner', label: 'کاملاً مبتدی' },
    { value: 'some-design', label: 'کمی طراحی بلدم' },
    { value: 'made-animation', label: 'قبلاً انیمیشن ساختم' },
    { value: 'unsure', label: 'مطمئن نیستم' },
];

export const CONSULTATION_INTEREST_OPTIONS: ConsultationFormOption[] = [
    { value: 'full-course', label: 'دوره جامع انیماتورشو' },
    { value: 'chapter', label: 'خرید فصل جداگانه' },
    { value: 'installment', label: 'خرید اقساطی' },
    { value: 'summer-class', label: 'کلاس تابستان' },
    { value: 'advice-only', label: 'فقط مشاوره می‌خوام' },
];

export const CONSULTATION_INTRO_BADGES: ConsultationIntroBadge[] = [
    { id: 'learning-path', label: 'انتخاب مسیر یادگیری' },
    { id: 'purchase-guide', label: 'راهنمای خرید دوره' },
    { id: 'installment-review', label: 'بررسی خرید اقساطی' },
    { id: 'summer-classes', label: 'کلاس‌های تابستان' },
];

export const CONSULTATION_TRUST_NOTE =
    'اطلاعات شما فقط برای پیگیری مشاوره و راهنمایی ثبت‌نام استفاده می‌شود.';

export const CONSULTATION_GUEST_CTA = {
    message:
        'برای ثبت درخواست مشاوره، ابتدا وارد حساب شوید و شماره موبایل خود را تأیید کنید.',
    loginLabel: 'ورود',
    registerLabel: 'ثبت‌نام',
} as const;

export const CONSULTATION_VERIFY_MOBILE_CTA = {
    message: 'برای ادامه، ابتدا شماره موبایل خود را ثبت و تأیید کنید.',
    ctaLabel: 'تأیید شماره موبایل',
} as const;

export const CONSULTATION_VERIFIED_MOBILE_COPY = {
    snapshotNote:
        'شماره تماس مشاوره از شماره موبایل تأییدشده حساب شما ثبت می‌شود.',
    mobileLabel: 'شماره موبایل تأییدشده',
} as const;

export const CONSULTATION_SUPPORT_FALLBACK = {
    title: 'سوال فوری داری؟',
    text: 'اگر قبلاً ثبت‌نام کردی یا درباره لایسنس و پرداخت سوال داری، از پشتیبانی پیام بفرست.',
    ctaLabel: 'رفتن به پشتیبانی',
    ctaHref: '/support',
} as const;
