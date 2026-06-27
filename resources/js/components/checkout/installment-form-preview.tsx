import { Spinner } from '@/components/ui/spinner';
import { CardToCardDetailsPanel } from '@/components/checkout/card-to-card-details-panel';
import { PaymentMethodSelector } from '@/components/checkout/payment-method-selector';
import {
    INSTALLMENT_DOWN_PAYMENT_CARD_TO_CARD_INSTRUCTIONS,
    INSTALLMENT_TERM_OPTIONS,
} from '@/lib/checkout-confirm';
import type { CheckoutOrderFormRenderProps } from '@/components/checkout/checkout-order-form';
import type {
    CardToCardTransferDetails,
    InstallmentPlan,
} from '@/lib/checkout-confirm';
import { formatTomanPrice } from '@/lib/format-toman';
import { cn } from '@/lib/utils';

const installmentFieldClassName =
    'border-border bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm text-start shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 dark:border-border dark:bg-surface dark:text-text';

const installmentTextareaClassName = cn(
    installmentFieldClassName,
    'min-h-[80px]',
);

type InstallmentFormFieldsProps = CheckoutOrderFormRenderProps & {
    plans?: InstallmentPlan[];
    cardToCardAvailable?: boolean;
    cardToCardUnavailableMessage?: string | null;
    cardToCardTransfer?: CardToCardTransferDetails | null;
    receiptFile?: File | null;
    onReceiptChange?: (file: File | null) => void;
    receiptError?: string;
};

export function InstallmentFormFields({
    processing,
    data,
    setData,
    errors,
    plans = [],
    selectedPaymentMethod,
    onSelectPaymentMethod,
    cardToCardAvailable = false,
    cardToCardUnavailableMessage = null,
    cardToCardTransfer = null,
    receiptFile = null,
    onReceiptChange,
    receiptError,
}: InstallmentFormFieldsProps) {
    const selectedPlan =
        plans.find((plan) => plan.term === data.installment_term) ?? null;
    const downPaymentAmountLine = selectedPlan
        ? formatTomanPrice(selectedPlan.downPaymentToman)
        : null;
    const isCardToCardDownPayment =
        selectedPaymentMethod === 'card-to-card' && cardToCardAvailable;

    return (
        <section
            className="flex w-full flex-col gap-4 rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border"
            aria-labelledby="installment-form-heading"
        >
            <h2
                id="installment-form-heading"
                className="text-center text-base font-bold text-text"
            >
                جزئیات درخواست اقساطی
            </h2>

            <p className="rounded-xl bg-purple-soft px-4 py-3 text-center text-sm leading-6 text-text ring-1 ring-purple/20">
                برای ثبت درخواست اقساطی، ابتدا پیش‌پرداخت
                {selectedPlan ? ` ${selectedPlan.downPaymentPercent}٪ ` : ' '}
                را به‌صورت آنلاین یا کارت‌به‌کارت پرداخت می‌کنید و سپس درخواست
                شما برای بررسی پشتیبانی ارسال می‌شود.
            </p>

            <div className="grid gap-2">
                <label
                    htmlFor="installment-note"
                    className="text-sm font-medium text-text"
                >
                    توضیح کوتاه
                </label>
                <textarea
                    id="installment-note"
                    name="note"
                    rows={3}
                    value={data.note ?? ''}
                    onChange={(event) => setData('note', event.target.value)}
                    className={installmentTextareaClassName}
                />
                {errors.note ? (
                    <p className="text-sm text-red">{errors.note}</p>
                ) : null}
            </div>

            <fieldset className="grid gap-2">
                <legend className="text-sm font-medium text-text">
                    مدت اقساط
                </legend>
                <div className="flex gap-3">
                    {INSTALLMENT_TERM_OPTIONS.map((term) => {
                        const isSelected = data.installment_term === term.id;

                        return (
                            <label
                                key={term.id}
                                className={cn(
                                    'flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-pill border px-3 py-2.5 text-sm font-medium transition-colors',
                                    isSelected
                                        ? 'border-purple bg-purple-soft text-text'
                                        : 'border-border bg-surface text-muted',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="installment_term"
                                    value={term.id}
                                    checked={isSelected}
                                    onChange={() =>
                                        setData('installment_term', term.id)
                                    }
                                    className="sr-only"
                                />
                                {term.label}
                            </label>
                        );
                    })}
                </div>
                {errors.installment_term ? (
                    <p className="text-sm text-red">{errors.installment_term}</p>
                ) : null}
            </fieldset>

            {selectedPlan ? (
                <dl className="grid grid-cols-2 gap-x-3 gap-y-2 rounded-2xl bg-purple-soft/60 p-4 text-sm ring-1 ring-purple/20">
                    <div className="flex flex-col">
                        <dt className="text-xs text-muted">قیمت نقدی</dt>
                        <dd className="font-medium text-text">
                            {formatTomanPrice(selectedPlan.cashPriceToman)}
                        </dd>
                    </div>
                    <div className="flex flex-col">
                        <dt className="text-xs text-muted">مبلغ کل اقساطی</dt>
                        <dd className="font-medium text-text">
                            {formatTomanPrice(
                                selectedPlan.installmentTotalToman,
                            )}
                        </dd>
                    </div>
                    <div className="flex flex-col">
                        <dt className="text-xs text-muted">
                            پیش‌پرداخت ({selectedPlan.downPaymentPercent}٪)
                        </dt>
                        <dd className="font-bold text-purple">
                            {formatTomanPrice(selectedPlan.downPaymentToman)}
                        </dd>
                    </div>
                    <div className="flex flex-col">
                        <dt className="text-xs text-muted">باقی‌مانده اقساط</dt>
                        <dd className="font-medium text-text">
                            {formatTomanPrice(selectedPlan.remainingToman)}
                        </dd>
                    </div>
                </dl>
            ) : null}

            <div className="flex w-full flex-col gap-3 border-t border-border pt-4">
                <PaymentMethodSelector
                    selectedPaymentMethod={selectedPaymentMethod}
                    onSelectPaymentMethod={onSelectPaymentMethod}
                    cardToCardAvailable={cardToCardAvailable}
                    embedded
                />

                {!cardToCardAvailable && cardToCardUnavailableMessage ? (
                    <p className="rounded-xl bg-gold-soft px-3 py-2.5 text-center text-xs font-medium leading-relaxed text-muted">
                        {cardToCardUnavailableMessage}
                    </p>
                ) : null}
            </div>

            {isCardToCardDownPayment &&
            cardToCardTransfer &&
            onReceiptChange ? (
                <CardToCardDetailsPanel
                    transferDetails={cardToCardTransfer}
                    amountLine={downPaymentAmountLine}
                    amountLabel="مبلغ پیش‌پرداخت قابل واریز"
                    instructions={
                        INSTALLMENT_DOWN_PAYMENT_CARD_TO_CARD_INSTRUCTIONS
                    }
                    receiptFile={receiptFile}
                    onReceiptChange={onReceiptChange}
                    receiptError={receiptError}
                    processing={processing}
                    submitLabel="ارسال رسید پیش‌پرداخت"
                    submitProcessingLabel="در حال ارسال رسید پیش‌پرداخت..."
                />
            ) : (
                <button
                    type="submit"
                    disabled={processing}
                    className="btn-cta-green flex h-12 w-full items-center justify-center gap-2 rounded-pill text-sm font-bold text-white disabled:opacity-70"
                >
                    {processing ? (
                        <>
                            <Spinner className="size-4" />
                            در حال انتقال به درگاه...
                        </>
                    ) : selectedPlan ? (
                        `پرداخت آنلاین پیش‌پرداخت ${formatTomanPrice(selectedPlan.downPaymentToman)}`
                    ) : (
                        'پرداخت پیش‌پرداخت و ثبت درخواست'
                    )}
                </button>
            )}
        </section>
    );
}
