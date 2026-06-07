/**
 * Maps quick-help item ids from the backend to support ticket category values.
 */
export const QUICK_HELP_CATEGORY_MAP: Record<string, string> = {
    license: 'license',
    payment: 'payment',
    course: 'course_access',
    installment: 'payment',
};

export function categoryForQuickHelpItem(itemId: string): string | null {
    return QUICK_HELP_CATEGORY_MAP[itemId] ?? null;
}
