import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import {
    getCheckoutResultContent,
    type CheckoutResultContent,
} from '@/lib/checkout-result-data';

export type CheckoutResultQuery = {
    rawStatus: string | null;
    orderNumber: string | null;
    content: CheckoutResultContent;
};

export function useCheckoutResultQuery(): CheckoutResultQuery {
    const { url } = usePage();

    return useMemo(() => {
        const searchParams = new URL(
            url,
            typeof window !== 'undefined'
                ? window.location.origin
                : 'http://localhost',
        ).searchParams;

        const rawStatus = searchParams.get('status');
        const orderNumber = searchParams.get('order');

        return {
            rawStatus,
            orderNumber,
            content: getCheckoutResultContent(rawStatus),
        };
    }, [url]);
}
