import {
    CHECKOUT_RESULT_LICENSE_NOTE,
    CHECKOUT_RESULT_PRODUCT_LABEL,
    type CheckoutResultContent,
} from '@/lib/checkout-result-data';

const cardClassName =
    'flex w-full flex-col gap-4 rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border';

type ResultOrderSummaryPlaceholderProps = {
    content: CheckoutResultContent;
    orderNumber?: string | null;
};

export function ResultOrderSummaryPlaceholder({
    content,
    orderNumber,
}: ResultOrderSummaryPlaceholderProps) {
    return (
        <article className={cardClassName}>
            <h2 className="text-base font-bold text-text">خلاصه سفارش</h2>

            <dl className="flex w-full flex-col gap-3">
                <div className="flex items-start justify-between gap-3 text-sm">
                    <dt className="font-medium text-muted">محصول</dt>
                    <dd className="text-end font-bold text-text">
                        {CHECKOUT_RESULT_PRODUCT_LABEL}
                    </dd>
                </div>

                {orderNumber ? (
                    <div className="flex items-start justify-between gap-3 text-sm">
                        <dt className="font-medium text-muted">شماره سفارش</dt>
                        <dd
                            className="text-end font-bold text-text"
                            dir="ltr"
                        >
                            {orderNumber}
                        </dd>
                    </div>
                ) : null}

                <div className="flex items-start justify-between gap-3 text-sm">
                    <dt className="font-medium text-muted">وضعیت</dt>
                    <dd className="text-end font-bold text-text">
                        {content.orderStatusLabel}
                    </dd>
                </div>

                <div className="flex items-start justify-between gap-3 text-sm">
                    <dt className="font-medium text-muted">لایسنس</dt>
                    <dd className="max-w-[12rem] text-end font-medium leading-relaxed text-muted">
                        {CHECKOUT_RESULT_LICENSE_NOTE}
                    </dd>
                </div>
            </dl>
        </article>
    );
}
