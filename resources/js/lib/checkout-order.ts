export type CheckoutOrderContext = {
    package: 'full' | 'chapter';
    payment: 'cash' | 'installment';
    chapter: string | null;
};
