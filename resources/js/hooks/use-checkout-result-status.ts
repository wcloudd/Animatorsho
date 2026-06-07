import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import {
    getCheckoutResultContent,
    type CheckoutResultContent,
} from '@/lib/checkout-result-data';

export type CheckoutResultQuery = {
    rawStatus: string | null;
    content: CheckoutResultContent;
};

export function useCheckoutResultStatus(): CheckoutResultQuery {
    const { url } = usePage();

    return useMemo(() => {
        const searchParams = new URL(
            url,
            typeof window !== 'undefined'
                ? window.location.origin
                : 'http://localhost',
        ).searchParams;

        const rawStatus = searchParams.get('status');

        return {
            rawStatus,
            content: getCheckoutResultContent(rawStatus),
        };
    }, [url]);
}
