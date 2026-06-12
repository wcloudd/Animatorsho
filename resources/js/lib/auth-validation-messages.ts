const EXACT_AUTH_ERROR_MESSAGES: Record<string, string> = {
    'These credentials do not match our records.':
        'اطلاعات واردشده درست نیست.',
    'The provided credentials are incorrect.':
        'اطلاعات واردشده درست نیست.',
    'The email field is required.': 'ایمیل را وارد کنید.',
    'The password field is required.': 'رمز عبور را وارد کنید.',
    'The name field is required.': 'نام و نام خانوادگی را وارد کنید.',
    'The identifier field is required.':
        'موبایل یا ایمیل خود را وارد کنید.',
    'The mobile field is required.': 'شماره موبایل را وارد کنید.',
    'The username field is required.': 'نام کاربری را وارد کنید.',
    'The code field is required.': 'کد تأیید را وارد کنید.',
    'The password confirmation field is required.':
        'تکرار رمز عبور را وارد کنید.',
    'The password confirmation does not match.':
        'تکرار رمز عبور با رمز عبور یکسان نیست.',
    'The password must be at least 8 characters.':
        'رمز عبور باید حداقل ۸ کاراکتر باشد.',
    'The email must be a valid email address.':
        'ایمیل واردشده معتبر نیست.',
    'The email has already been taken.':
        'این ایمیل قبلاً ثبت شده است.',
    'Too many login attempts. Please try again in :seconds seconds.':
        'تلاش‌های ورود زیاد بود. لطفاً چند لحظه بعد دوباره تلاش کن.',
    'We have emailed your password reset link!':
        'لینک بازیابی رمز عبور به ایمیلت ارسال شد.',
    'Your password has been reset.': 'رمز عبور با موفقیت تغییر کرد.',
};

const FIELD_REQUIRED_PATTERNS: { pattern: RegExp; message: string }[] = [
    {
        pattern: /^The email field is required\.?$/i,
        message: 'ایمیل را وارد کنید.',
    },
    {
        pattern: /^The password field is required\.?$/i,
        message: 'رمز عبور را وارد کنید.',
    },
    {
        pattern: /^The name field is required\.?$/i,
        message: 'نام و نام خانوادگی را وارد کنید.',
    },
    {
        pattern: /^The password confirmation field is required\.?$/i,
        message: 'تکرار رمز عبور را وارد کنید.',
    },
    {
        pattern: /^The identifier field is required\.?$/i,
        message: 'موبایل یا ایمیل خود را وارد کنید.',
    },
    {
        pattern: /^The mobile field is required\.?$/i,
        message: 'شماره موبایل را وارد کنید.',
    },
    {
        pattern: /^The username field is required\.?$/i,
        message: 'نام کاربری را وارد کنید.',
    },
    {
        pattern: /^The code field is required\.?$/i,
        message: 'کد تأیید را وارد کنید.',
    },
];

export function localizeAuthError(message?: string): string | undefined {
    if (!message) {
        return undefined;
    }

    const trimmed = message.trim();

    if (EXACT_AUTH_ERROR_MESSAGES[trimmed]) {
        return EXACT_AUTH_ERROR_MESSAGES[trimmed];
    }

    for (const { pattern, message: localized } of FIELD_REQUIRED_PATTERNS) {
        if (pattern.test(trimmed)) {
            return localized;
        }
    }

    if (/credentials do not match|provided credentials are incorrect/i.test(trimmed)) {
        return 'اطلاعات واردشده درست نیست.';
    }

    if (/email.*required/i.test(trimmed)) {
        return 'ایمیل را وارد کنید.';
    }

    if (/password confirmation.*required/i.test(trimmed)) {
        return 'تکرار رمز عبور را وارد کنید.';
    }

    if (/password.*required/i.test(trimmed)) {
        return 'رمز عبور را وارد کنید.';
    }

    if (/name.*required/i.test(trimmed)) {
        return 'نام و نام خانوادگی را وارد کنید.';
    }

    return trimmed;
}

export function localizeAuthStatus(status?: string): string | undefined {
    if (!status) {
        return undefined;
    }

    return localizeAuthError(status) ?? status;
}
