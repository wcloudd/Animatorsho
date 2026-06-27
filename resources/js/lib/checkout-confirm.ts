export type CheckoutPackage = 'full' | 'chapter';

export type CheckoutPayment = 'cash' | 'installment';

export type OrderSummaryVariant =
    | 'full-cash'
    | 'full-installment'
    | 'chapter';

export type OrderSummaryContent = {
    variant: OrderSummaryVariant;
    title: string;
    paymentType: string;
    priceLine?: string;
    mainLine?: string;
    description: string;
    primaryCtaLabel: string;
    primaryCtaHref: string;
};

export const PAYMENT_METHOD_OPTIONS = [
    {
        id: 'online',
        title: 'پرداخت آنلاین',
        description: 'پرداخت امن از طریق درگاه زرین‌پال.',
    },
    {
        id: 'card-to-card',
        title: 'کارت‌به‌کارت',
        description: 'واریز به حساب و ارسال رسید برای بررسی پشتیبانی.',
    },
] as const;

export type CheckoutPaymentMethodId =
    (typeof PAYMENT_METHOD_OPTIONS)[number]['id'];

export type CardToCardTransferDetails = {
    cardNumber: string;
    cardOwnerName: string;
};

export const CARD_TO_CARD_INSTRUCTIONS = [
    'مبلغ را دقیقاً مطابق سفارش واریز کنید.',
    'تصویر رسید را بارگذاری کنید.',
    'پشتیبانی رسید را بررسی می‌کند.',
    'بعد از تأیید، دسترسی و لایسنس فعال می‌شود.',
] as const;

export const INSTALLMENT_DOWN_PAYMENT_CARD_TO_CARD_INSTRUCTIONS = [
    'مبلغ پیش‌پرداخت را دقیقاً مطابق همین مقدار واریز کنید.',
    'تصویر رسید واریز پیش‌پرداخت را بارگذاری کنید.',
    'پشتیبانی رسید پیش‌پرداخت را بررسی می‌کند.',
    'بعد از تأیید پیش‌پرداخت، درخواست اقساطی شما برای بررسی نهایی ادامه پیدا می‌کند.',
] as const;

export const INSTALLMENT_TERM_OPTIONS = [
    { id: 'one_month', label: '۱ ماهه' },
    { id: 'two_months', label: '۲ ماهه' },
] as const;

export type InstallmentPlan = {
    term: string;
    label: string;
    months: number;
    downPaymentPercent: number;
    cashPriceToman: number;
    extraAmountToman: number;
    installmentTotalToman: number;
    downPaymentToman: number;
    remainingToman: number;
};

export const TRUST_NOTE_TEXT =
    'فعال‌سازی دسترسی دوره بعد از تأیید پرداخت یا بررسی درخواست اقساطی انجام می‌شود. اطلاعات سفارش و لایسنس‌ها بعداً در پروفایل کاربری نمایش داده خواهد شد.';
