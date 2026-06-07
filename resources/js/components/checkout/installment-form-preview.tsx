import { Spinner } from '@/components/ui/spinner';
import { INSTALLMENT_TERM_OPTIONS } from '@/lib/checkout-confirm';
import type { CheckoutOrderFormRenderProps } from '@/components/checkout/checkout-order-form';
import { cn } from '@/lib/utils';

const installmentFieldClassName =
    'border-border bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm text-start shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 dark:border-border dark:bg-surface dark:text-text';

const installmentTextareaClassName = cn(
    installmentFieldClassName,
    'min-h-[80px]',
);

type InstallmentFormFieldsProps = CheckoutOrderFormRenderProps;

export function InstallmentFormFields({
    processing,
    data,
    setData,
    errors,
}: InstallmentFormFieldsProps) {
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
                درخواست خرید اقساطی ثبت می‌شود و پشتیبانی برای هماهنگی با
                شما تماس می‌گیرد.
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

            <button
                type="submit"
                disabled={processing}
                className="btn-cta-green flex h-12 w-full items-center justify-center gap-2 rounded-pill text-sm font-bold text-white disabled:opacity-70"
            >
                {processing ? (
                    <>
                        <Spinner className="size-4" />
                        در حال ثبت درخواست...
                    </>
                ) : (
                    'ثبت درخواست اقساطی'
                )}
            </button>
        </section>
    );
}
