import { ArrowLeftRight, CreditCard } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import {
    PAYMENT_METHOD_OPTIONS,
    type CheckoutPaymentMethodId,
} from '@/lib/checkout-confirm';
import { cn } from '@/lib/utils';

const PAYMENT_METHOD_ICONS: Record<CheckoutPaymentMethodId, LucideIcon> = {
    online: CreditCard,
    'card-to-card': ArrowLeftRight,
};

type PaymentMethodRadioCardProps = {
    method: (typeof PAYMENT_METHOD_OPTIONS)[number];
    selected: boolean;
    onSelect: () => void;
    embedded?: boolean;
};

function PaymentMethodRadioCard({
    method,
    selected,
    onSelect,
    embedded = false,
}: PaymentMethodRadioCardProps) {
    const Icon = PAYMENT_METHOD_ICONS[method.id];
    const inputId = `payment-method-${method.id}`;

    return (
        <label
            htmlFor={inputId}
            className={cn(
                'flex h-[78px] w-full cursor-pointer items-center justify-center gap-3 rounded-2xl px-4 text-start shadow-soft ring-1 transition-colors',
                embedded ? 'bg-bg' : 'bg-surface',
                selected
                    ? 'ring-2 ring-purple'
                    : 'ring-border hover:bg-purple-soft/40',
            )}
        >
            <input
                id={inputId}
                type="radio"
                name="checkout-payment-method"
                value={method.id}
                checked={selected}
                onChange={onSelect}
                className="sr-only"
            />
            <div
                className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-purple-soft text-purple"
                aria-hidden
            >
                <Icon className="size-5 stroke-[1.75]" />
            </div>
            <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                <span className="text-sm font-bold text-text">
                    {method.title}
                </span>
                <span className="text-xs font-medium leading-relaxed text-muted">
                    {method.description}
                </span>
            </div>
            <span
                className={cn(
                    'flex size-5 shrink-0 items-center justify-center rounded-full border-2 transition-colors',
                    selected
                        ? 'border-purple bg-purple'
                        : 'border-border bg-surface',
                )}
                aria-hidden
            >
                <span
                    className={cn(
                        'size-2 rounded-full bg-white transition-opacity',
                        selected ? 'opacity-100' : 'opacity-0',
                    )}
                />
            </span>
        </label>
    );
}

type PaymentMethodSelectorProps = {
    selectedPaymentMethod: CheckoutPaymentMethodId;
    onSelectPaymentMethod: (methodId: CheckoutPaymentMethodId) => void;
    cardToCardAvailable?: boolean;
    embedded?: boolean;
};

/**
 * Shared payment-method picker (title + stacked option cards) used by both the
 * full/cash checkout and the installment down-payment flow so they stay visually
 * identical. Only the surrounding context (what the selected method pays) differs.
 */
export function PaymentMethodSelector({
    selectedPaymentMethod,
    onSelectPaymentMethod,
    cardToCardAvailable = false,
    embedded = false,
}: PaymentMethodSelectorProps) {
    const visibleMethods = PAYMENT_METHOD_OPTIONS.filter(
        (method) => method.id !== 'card-to-card' || cardToCardAvailable,
    );

    return (
        <>
            <h2
                id="payment-methods-heading"
                className={cn(
                    'font-bold text-text',
                    embedded ? 'text-center text-sm' : 'text-center text-base',
                )}
            >
                روش پرداخت
            </h2>
            <fieldset className="flex w-full flex-col gap-3 border-0 p-0">
                <legend className="sr-only">انتخاب روش پرداخت</legend>
                {visibleMethods.map((method) => (
                    <PaymentMethodRadioCard
                        key={method.id}
                        method={method}
                        selected={selectedPaymentMethod === method.id}
                        onSelect={() => onSelectPaymentMethod(method.id)}
                        embedded={embedded}
                    />
                ))}
            </fieldset>
        </>
    );
}
