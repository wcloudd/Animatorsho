export { ANIMATORSHO_LOGO_SRC, BRAND_LOGO_SRC } from '@/lib/brand-assets';

export const AUTH_BACK_TO_HOME_LABEL = 'بازگشت به سایت' as const;

export const AUTH_SUPPORT_EITAA_URL =
    'https://web.eitaa.com/#@nimvajabee_admin' as const;

export const AUTH_SUPPORT_FALLBACK = {
    title: 'مشکل ورود داری؟',
    text: 'اگر نمی‌تونی وارد حساب بشی یا موقع ثبت‌نام خطا می‌گیری، از پشتیبانی کمک بگیر.',
    ctaLabel: 'ارتباط با پشتیبان',
} as const;

export const AUTH_REGISTER_TRUST_NOTE =
    'اطلاعات حساب برای مدیریت سفارش، لایسنس SpotPlayer و پشتیبانی دوره استفاده می‌شود.' as const;

export const AUTH_LOGIN_IDENTIFIER_COPY = {
    headTitle: 'ورود یا ثبت‌نام',
    title: 'ورود یا ثبت‌نام در انیماتورشو',
    identifierLabel: 'برای ورود یا ثبت‌نام، اطلاعات کاربری خود را وارد کنید:',
    identifierPlaceholder: 'موبایل یا ایمیل خود را وارد کنید',
    termsNoteBeforeLink: 'با ادامه دادن، ',
    termsLinkLabel: 'قوانین',
    termsNoteAfterLink: ' انیماتورشو را می‌پذیرید.',
    submitLabel: 'ادامه',
} as const;

export const AUTH_LOGIN_COPY = {
    headTitle: 'ورود',
    title: 'ورود به انیماتورشو',
    subtitle:
        'با شماره موبایل و رمز عبور وارد حساب کاربری‌ات شو.',
    mobileLabel: 'شماره موبایل',
    mobilePlaceholder: '09123456789',
    passwordLabel: 'رمز عبور',
    passwordPlaceholder: 'رمز عبور',
    rememberLabel: 'مرا به خاطر بسپار',
    forgotPasswordLabel: 'فراموشی رمز عبور',
    submitLabel: 'ورود به حساب',
    otpLoginLabel: 'دریافت کد ورود از طریق پیامک',
    legacyEmailLoginLabel: 'ورود با ایمیل برای حساب‌های قدیمی / ادمین',
    secondaryPrompt: 'هنوز ثبت‌نام نکردی؟',
    secondaryLinkLabel: 'ثبت‌نام',
} as const;

export const AUTH_LOGIN_PASSWORD_COPY = {
    headTitle: 'ورود با رمز عبور',
    title: 'ورود با رمز عبور',
    subtitle: 'رمز عبور حساب {mobile} را وارد کن.',
    passwordLabel: 'رمز عبور',
    passwordPlaceholder: 'رمز عبور',
    rememberLabel: 'مرا به خاطر بسپار',
    forgotPasswordLabel: 'فراموشی رمز عبور',
    submitLabel: 'ورود به حساب',
    otpLoginLabel: 'ورود با کد یکبارمصرف',
} as const;

export const AUTH_LOGIN_EMAIL_COPY = {
    headTitle: 'ورود با ایمیل',
    title: 'ورود با ایمیل',
    subtitle:
        'برای حساب‌های قدیمی یا ادمین که با ایمیل ثبت‌نام کرده‌اند.',
    emailLabel: 'ایمیل',
    emailPlaceholder: 'example@email.com',
    passwordLabel: 'رمز عبور',
    passwordPlaceholder: 'رمز عبور',
    rememberLabel: 'مرا به خاطر بسپار',
    forgotPasswordLabel: 'فراموشی رمز عبور',
    submitLabel: 'ورود به حساب',
    primaryLoginLabel: 'ورود یا ثبت‌نام با موبایل یا ایمیل',
    secondaryPrompt: 'هنوز ثبت‌نام نکردی؟',
    secondaryLinkLabel: 'ثبت‌نام',
} as const;

export const AUTH_REGISTER_COPY = {
    headTitle: 'ثبت‌نام',
    title: 'تکمیل ثبت‌نام در انیماتورشو',
    subtitle:
        'اطلاعات حساب کاربری‌ات را وارد کن تا کد تأیید برایت ارسال شود.',
    nameLabel: 'نام نمایشی',
    namePlaceholder: 'نام نمایشی',
    usernameLabel: 'نام کاربری',
    usernamePlaceholder: 'nimvajabee',
    usernameHint:
        'نام کاربری فقط با حروف انگلیسی کوچک، عدد و خط زیر مجاز است.',
    emailLabel: 'ایمیل (اختیاری)',
    emailPlaceholder: 'example@email.com',
    emailHint: 'اختیاری؛ برای بازیابی رمز در صورت قطع بودن پیامک',
    mobileLabel: 'شماره موبایل',
    mobilePlaceholder: '09123456789',
    passwordLabel: 'رمز عبور',
    passwordPlaceholder: 'رمز عبور',
    passwordConfirmLabel: 'تکرار رمز عبور',
    passwordConfirmPlaceholder: 'تکرار رمز عبور',
    submitLabel: 'ساخت حساب کاربری',
    secondaryPrompt: 'قبلاً ثبت‌نام کردی؟',
    secondaryLinkLabel: 'ورود به حساب',
} as const;

export const AUTH_REGISTER_VERIFY_COPY = {
    headTitle: 'تأیید ثبت‌نام',
    title: 'کد فرستاده‌شده را وارد کنید',
    subtitle: 'کد ۶ رقمی ارسال‌شده به {mobile} را وارد کنید.',
    codeLabel: 'کد تأیید',
    codePlaceholder: '123456',
    submitLabel: 'تکمیل ثبت‌نام',
    resendLabel: 'ارسال دوباره کد',
    resendWaitLabel: 'ارسال دوباره تا {seconds} ثانیه دیگر',
    changeMobileLabel: 'تغییر شماره موبایل',
    changeMobileSubmitLabel: 'ارسال کد به شماره جدید',
    sentStatus: 'در صورت معتبر بودن شماره، کد ارسال شد.',
} as const;

export const AUTH_FORGOT_PASSWORD_COPY = {
    headTitle: 'بازیابی رمز عبور',
    title: 'بازیابی رمز عبور',
    subtitle: 'روش بازیابی را انتخاب کن.',
    mobileTabLabel: 'شماره موبایل',
    emailTabLabel: 'ایمیل',
    mobileSubtitle: 'شماره موبایل تأییدشده حسابت را وارد کن تا کد بازیابی ارسال شود.',
    emailSubtitle: 'ایمیل بازیابی حسابت را وارد کن تا لینک بازیابی ارسال شود.',
    mobileLabel: 'شماره موبایل',
    mobilePlaceholder: '09123456789',
    mobileSubmitLabel: 'دریافت کد بازیابی',
    emailLabel: 'ایمیل',
    emailPlaceholder: 'example@email.com',
    emailSubmitLabel: 'ارسال لینک بازیابی',
    smsUnavailableMessage:
        'بازیابی با شماره موبایل فعلاً در دسترس نیست. اگر ایمیل بازیابی ثبت کرده‌اید، از بازیابی با ایمیل استفاده کنید.',
    emailFallbackHint:
        'اگر پیامک در دسترس نبود، از بازیابی با ایمیل استفاده کنید.',
    secondaryPrompt: 'یا',
    secondaryLinkLabel: 'بازگشت به ورود',
    sentStatus: 'در صورت معتبر بودن شماره، کد ارسال شد.',
} as const;

export const AUTH_FORGOT_PASSWORD_VERIFY_COPY = {
    headTitle: 'تأیید کد بازیابی',
    title: 'کد فرستاده‌شده را وارد کنید',
    subtitle: 'کد ۶ رقمی ارسال‌شده به {mobile} را وارد کنید.',
    codeLabel: 'کد بازیابی',
    codePlaceholder: '123456',
    submitLabel: 'ادامه',
    resendLabel: 'ارسال دوباره کد',
    resendWaitLabel: 'ارسال دوباره تا {seconds} ثانیه دیگر',
    changeMobileLabel: 'تغییر شماره موبایل',
    emailFallbackHint:
        'اگر پیامک در دسترس نبود، از بازیابی با ایمیل استفاده کنید.',
    sentStatus: 'در صورت معتبر بودن شماره، کد ارسال شد.',
} as const;

export const AUTH_RESET_PASSWORD_MOBILE_COPY = {
    headTitle: 'رمز عبور جدید',
    title: 'تنظیم رمز عبور جدید',
    subtitle: 'رمز عبور جدید حساب {mobile} را وارد کن.',
    passwordLabel: 'رمز عبور جدید',
    passwordPlaceholder: 'رمز عبور جدید',
    passwordConfirmLabel: 'تکرار رمز عبور',
    passwordConfirmPlaceholder: 'تکرار رمز عبور',
    submitLabel: 'ذخیره رمز عبور جدید',
} as const;

export const AUTH_MOBILE_COPY = {
    headTitle: 'ورود با کد یکبار مصرف',
    title: 'ورود با کد یکبارمصرف',
    subtitle:
        'اگر قبلاً ثبت‌نام کرده‌ای، شماره موبایلت را وارد کن تا کد ورود برایت ارسال شود.',
    mobileLabel: 'شماره موبایل',
    mobilePlaceholder: '09123456789',
    submitLabel: 'دریافت کد ورود',
    secondaryPrompt: 'یا',
    secondaryLinkLabel: 'ورود با شماره موبایل و رمز عبور',
    registerPrompt: 'هنوز ثبت‌نام نکردی؟',
    registerLinkLabel: 'ثبت‌نام',
    sentStatus: 'در صورت معتبر بودن شماره، کد ارسال شد.',
} as const;

export const AUTH_MOBILE_VERIFY_COPY = {
    headTitle: 'ورود با کد یکبار مصرف',
    title: 'کد فرستاده‌شده را وارد کنید',
    subtitle: 'کد ۶ رقمی ارسال‌شده به {mobile} را وارد کنید.',
    codeLabel: 'کد ورود',
    codePlaceholder: '123456',
    submitLabel: 'ورود به حساب',
    resendLabel: 'ارسال دوباره کد',
    resendWaitLabel: 'ارسال دوباره تا {seconds} ثانیه دیگر',
    changeMobileLabel: 'تغییر شماره موبایل',
    passwordLoginLabel: 'ورود با رمز عبور',
    secondaryLinkLabel: 'ورود با شماره موبایل و رمز عبور',
    registerPrompt: 'هنوز ثبت‌نام نکردی؟',
    registerLinkLabel: 'ثبت‌نام',
    sentStatus: 'در صورت معتبر بودن شماره، کد ارسال شد.',
} as const;

export const VERIFY_MOBILE_CTA = {
    message: 'برای ادامه، ابتدا شماره موبایل خود را ثبت و تأیید کنید.',
    ctaLabel: 'تأیید شماره موبایل',
} as const;

export const PROFILE_MOBILE_VERIFY_COPY = {
    headTitle: 'تأیید موبایل',
    title: 'ثبت و تأیید شماره موبایل',
    subtitle:
        'برای ادامه، شماره موبایل خود را وارد کنید تا کد تأیید برایت ارسال شود.',
    existingSubtitle:
        'برای ادامه، کد تأیید را برای شماره موبایل ثبت‌شده در حساب کاربری‌ات ارسال می‌کنیم.',
    requiredMessage:
        'برای ادامه، ابتدا شماره موبایل خود را ثبت و تأیید کنید.',
    mobileLabel: 'شماره موبایل',
    mobilePlaceholder: '09123456789',
    submitLabel: 'دریافت کد تأیید',
    verifyExistingLabel: 'ارسال کد تأیید',
    changeMobileLabel: 'تغییر شماره موبایل',
    useExistingMobileLabel: 'تأیید همین شماره',
    sentStatus: 'در صورت معتبر بودن شماره، کد ارسال شد.',
} as const;

export const PROFILE_MOBILE_VERIFY_OTP_COPY = {
    headTitle: 'تأیید کد',
    title: 'کد تأیید را وارد کن',
    subtitle: 'کد ۶ رقمی ارسال‌شده به {mobile} را وارد کن.',
    codeLabel: 'کد تأیید',
    codePlaceholder: '123456',
    submitLabel: 'تأیید شماره موبایل',
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
