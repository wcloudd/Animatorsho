import { useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import {
    INSTALLMENT_TERM_OPTIONS,
    type CheckoutPaymentMethodId,
} from '@/lib/checkout-confirm';
import type { CheckoutOrderContext } from '@/lib/checkout-order';

type CheckoutOrderFormData = {
    package: string;
    payment: string;
    chapter?: string;
    customer_name: string;
    customer_mobile: string;
    payment_channel: string;
    installment_term?: string;
    note?: string;
    receipt_image: File | null;
};

type CheckoutOrderFormRenderProps = {
    processing: boolean;
    data: CheckoutOrderFormData;
    setData: <K extends keyof CheckoutOrderFormData>(
        key: K,
        value: CheckoutOrderFormData[K],
    ) => void;
    errors: Partial<Record<string, string>>;
    selectedPaymentMethod: CheckoutPaymentMethodId;
    onSelectPaymentMethod: (methodId: CheckoutPaymentMethodId) => void;
};

type CheckoutOrderFormProps = {
    context: CheckoutOrderContext;
    customerDefaults?: { name: string } | null;
    className?: string;
    selectedPaymentMethod: CheckoutPaymentMethodId;
    onSelectPaymentMethod: (methodId: CheckoutPaymentMethodId) => void;
    children:
        | ReactNode
        | ((props: CheckoutOrderFormRenderProps) => ReactNode);
};

export function CheckoutOrderForm({
    context,
    customerDefaults = null,
    className,
    selectedPaymentMethod,
    onSelectPaymentMethod,
    children,
}: CheckoutOrderFormProps) {
    const isInstallment = context.payment === 'installment';

    const form = useForm<CheckoutOrderFormData>({
        package: context.package,
        payment: context.payment,
        ...(context.chapter ? { chapter: context.chapter } : {}),
        customer_name: customerDefaults?.name ?? '',
        customer_mobile: '',
        payment_channel: 'online',
        receipt_image: null,
        ...(isInstallment
            ? {
                  installment_term:
                      INSTALLMENT_TERM_OPTIONS[0]?.id ?? 'one_month',
                  note: '',
              }
            : {}),
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const isCardToCard =
            context.payment === 'cash' &&
            selectedPaymentMethod === 'card-to-card';

        form.transform((data) => ({
            ...data,
            payment_channel: isCardToCard ? 'card_to_card' : 'online',
        }));

        form.post('/checkout/orders', {
            preserveScroll: true,
            forceFormData: isCardToCard,
        });
    };

    const renderProps: CheckoutOrderFormRenderProps = {
        processing: form.processing,
        data: form.data,
        setData: form.setData,
        errors: form.errors,
        selectedPaymentMethod,
        onSelectPaymentMethod,
    };

    return (
        <form onSubmit={handleSubmit} className={className}>
            {typeof children === 'function'
                ? children(renderProps)
                : children}
        </form>
    );
}

export type { CheckoutOrderFormRenderProps };
