export const CHECKOUT_FULL_URL = '/checkout?package=full' as const;

export const CHECKOUT_CASH_URL =
    '/checkout/confirm?package=full&payment=cash' as const;

export const CHECKOUT_INSTALLMENT_URL =
    '/checkout/confirm?package=full&payment=installment' as const;

export const CHECKOUT_CHAPTER_URL =
    '/checkout/confirm?package=chapter' as const;

export function checkoutChapterConfirmUrl(chapterSlug: string): string {
    const params = new URLSearchParams({
        package: 'chapter',
        chapter: chapterSlug,
    });

    return `/checkout/confirm?${params.toString()}`;
}

export const CHECKOUT_PURCHASE_RULES_URL =
    '/checkout#purchase-rules' as const;
