export const ANIMATORSHO_LOGO_SRC = '/images/animatorsho-logo.svg?v=2' as const;

export const AUTH_BACK_TO_HOME_LABEL = 'بازگشت به انیماتورشو' as const;

export const AUTH_SUPPORT_EITAA_URL =
    'https://web.eitaa.com/#@nimvajabee_admin' as const;

export const AUTH_SUPPORT_FALLBACK = {
    title: 'مشکل ورود داری؟',
    text: 'اگر نمی‌تونی وارد حساب بشی یا موقع ثبت‌نام خطا می‌گیری، از پشتیبانی کمک بگیر.',
    ctaLabel: 'ارتباط با پشتیبان',
} as const;

export const AUTH_REGISTER_TRUST_NOTE =
    'اطلاعات حساب برای مدیریت سفارش، لایسنس SpotPlayer و پشتیبانی دوره استفاده می‌شود.' as const;

export const AUTH_LOGIN_COPY = {
    headTitle: 'ورود',
    title: 'ورود به انیماتورشو',
    subtitle:
        'برای دیدن دوره‌ها، لایسنس‌ها و پشتیبانی وارد حساب کاربری‌ات شو.',
    emailLabel: 'ایمیل',
    emailPlaceholder: 'example@email.com',
    passwordLabel: 'رمز عبور',
    passwordPlaceholder: 'رمز عبور',
    rememberLabel: 'مرا به خاطر بسپار',
    forgotPasswordLabel: 'فراموشی رمز عبور',
    submitLabel: 'ورود به حساب',
    secondaryPrompt: 'هنوز ثبت‌نام نکردی؟',
    secondaryLinkLabel: 'ثبت‌نام در انیماتورشو',
} as const;

export const AUTH_REGISTER_COPY = {
    headTitle: 'ثبت‌نام',
    title: 'ثبت‌نام در انیماتورشو',
    subtitle:
        'حساب کاربری بساز تا سفارش‌ها، لایسنس‌ها و مسیر یادگیری‌ات ذخیره شود.',
    nameLabel: 'نام و نام خانوادگی',
    namePlaceholder: 'نام کامل',
    emailLabel: 'ایمیل',
    emailPlaceholder: 'example@email.com',
    passwordLabel: 'رمز عبور',
    passwordPlaceholder: 'رمز عبور',
    passwordConfirmLabel: 'تکرار رمز عبور',
    passwordConfirmPlaceholder: 'تکرار رمز عبور',
    submitLabel: 'ساخت حساب کاربری',
    secondaryPrompt: 'قبلاً ثبت‌نام کردی؟',
    secondaryLinkLabel: 'ورود به حساب',
} as const;

export const AUTH_FORGOT_PASSWORD_COPY = {
    headTitle: 'بازیابی رمز عبور',
    title: 'بازیابی رمز عبور',
    subtitle: 'ایمیل حسابت را وارد کن تا لینک بازیابی برایت ارسال شود.',
    emailLabel: 'ایمیل',
    emailPlaceholder: 'example@email.com',
    submitLabel: 'ارسال لینک بازیابی',
    secondaryPrompt: 'یا',
    secondaryLinkLabel: 'بازگشت به ورود',
} as const;

export const AUTH_MOBILE_COPY = {
    headTitle: 'ورود با موبایل',
    title: 'ورود با شماره موبایل',
    subtitle: 'شماره موبایلت را وارد کن تا کد ورود برایت ارسال شود.',
    mobileLabel: 'شماره موبایل',
    mobilePlaceholder: '09123456789',
    submitLabel: 'دریافت کد ورود',
    secondaryPrompt: 'یا',
    secondaryLinkLabel: 'ورود با ایمیل',
    sentStatus: 'در صورت معتبر بودن شماره، کد ارسال شد.',
} as const;

export const AUTH_MOBILE_VERIFY_COPY = {
    headTitle: 'تأیید کد',
    title: 'کد ورود را وارد کن',
    subtitle: 'کد ۶ رقمی ارسال‌شده به {mobile} را وارد کن.',
    codeLabel: 'کد ورود',
    codePlaceholder: '123456',
    submitLabel: 'ورود به حساب',
    resendLabel: 'ارسال مجدد کد',
    resendWaitLabel: 'ارسال مجدد تا {seconds} ثانیه دیگر',
    changeMobileLabel: 'تغییر شماره موبایل',
    sentStatus: 'در صورت معتبر بودن شماره، کد ارسال شد.',
} as const;

export const AUTH_RESET_PASSWORD_COPY = {
    headTitle: 'رمز عبور جدید',
    title: 'تنظیم رمز عبور جدید',
    subtitle: 'رمز عبور جدید حسابت را وارد کن.',
    emailLabel: 'ایمیل',
    passwordLabel: 'رمز عبور جدید',
    passwordPlaceholder: 'رمز عبور جدید',
    passwordConfirmLabel: 'تکرار رمز عبور',
    passwordConfirmPlaceholder: 'تکرار رمز عبور',
    submitLabel: 'ذخیره رمز عبور جدید',
} as const;
