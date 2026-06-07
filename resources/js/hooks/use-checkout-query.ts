import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { CheckoutPackage, CheckoutPayment } from '@/lib/checkout-confirm';

export type CheckoutQuery = {
    package: CheckoutPackage | null;
    payment: CheckoutPayment | null;
    rawPackage: string | null;
    rawPayment: string | null;
};

function parsePackage(value: string | null): CheckoutPackage | null {
    if (value === 'full' || value === 'chapter') {
        return value;
    }

    return null;
}

function parsePayment(value: string | null): CheckoutPayment | null {
    if (value === 'cash' || value === 'installment') {
        return value;
    }

    return null;
}

export function useCheckoutQuery(): CheckoutQuery {
    const { url } = usePage();

    return useMemo(() => {
        const searchParams = new URL(
            url,
            typeof window !== 'undefined'
                ? window.location.origin
                : 'http://localhost',
        ).searchParams;

        const rawPackage = searchParams.get('package');
        const rawPayment = searchParams.get('payment');

        return {
            package: parsePackage(rawPackage),
            payment: parsePayment(rawPayment),
            rawPackage,
            rawPayment,
        };
    }, [url]);
}
