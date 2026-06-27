import { CardToCardDetailsPanel } from '@/components/checkout/card-to-card-details-panel';
import { PaymentMethodSelector } from '@/components/checkout/payment-method-selector';
import { Spinner } from '@/components/ui/spinner';
import type {
    CardToCardTransferDetails,
    CheckoutPaymentMethodId,
} from '@/lib/checkout-confirm';
import type { CheckoutOrderContext } from '@/lib/checkout-order';

type PaymentMethodsPreviewProps = {
    orderContext: CheckoutOrderContext | null;
    selectedPaymentMethod: CheckoutPaymentMethodId;
    onSelectPaymentMethod: (methodId: CheckoutPaymentMethodId) => void;
    embedded?: boolean;
    processing?: boolean;
    cardToCardAvailable?: boolean;
    cardToCardUnavailableMessage?: string | null;
    cardToCardTransfer?: CardToCardTransferDetails | null;
    amountLine?: string | null;
    receiptFile?: File | null;
    onReceiptChange?: (file: File | null) => void;
    receiptError?: string;
};

export function PaymentMethodsPreview({
    orderContext,
    selectedPaymentMethod,
    onSelectPaymentMethod,
    embedded = false,
    processing = false,
    cardToCardAvailable = false,
    cardToCardUnavailableMessage = null,
    cardToCardTransfer = null,
    amountLine = null,
    receiptFile = null,
    onReceiptChange,
    receiptError,
}: PaymentMethodsPreviewProps) {
    const canSubmitOnline =
        orderContext !== null && orderContext.payment === 'cash';

    const content = (
        <>
            <PaymentMethodSelector
                selectedPaymentMethod={selectedPaymentMethod}
                onSelectPaymentMethod={onSelectPaymentMethod}
                cardToCardAvailable={cardToCardAvailable}
                embedded={embedded}
            />

            {!cardToCardAvailable && cardToCardUnavailableMessage ? (
                <p className="rounded-xl bg-gold-soft px-3 py-2.5 text-center text-xs font-medium leading-relaxed text-muted">
                    {cardToCardUnavailableMessage}
                </p>
            ) : null}

            {selectedPaymentMethod === 'card-to-card' &&
            cardToCardAvailable &&
            cardToCardTransfer &&
            onReceiptChange ? (
                <CardToCardDetailsPanel
                    transferDetails={cardToCardTransfer}
                    amountLine={amountLine}
                    receiptFile={receiptFile}
                    onReceiptChange={onReceiptChange}
                    receiptError={receiptError}
                    processing={processing}
                />
            ) : null}

            {selectedPaymentMethod === 'online' && canSubmitOnline ? (
                <button
                    type="submit"
                    disabled={processing}
                    className="btn-cta-green flex h-12 w-full items-center justify-center gap-2 rounded-pill text-base font-bold text-white shadow-soft disabled:opacity-70"
                >
                    {processing ? (
                        <>
                            <Spinner className="size-4" />
                            در حال ثبت سفارش...
                        </>
                    ) : (
                        'ورود به درگاه پرداخت'
                    )}
                </button>
            ) : null}
        </>
    );

    if (embedded) {
        return (
            <div
                className="flex w-full flex-col gap-3 border-t border-border pt-4"
                aria-labelledby="payment-methods-heading"
            >
                {content}
            </div>
        );
    }

    return (
        <section
            className="flex w-full flex-col gap-3"
            aria-labelledby="payment-methods-heading"
        >
            {content}
        </section>
    );
}
