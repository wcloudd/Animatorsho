import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { ChapterSelector } from '@/components/checkout/chapter-selector';
import type { CatalogPackage } from '@/lib/checkout-catalog';
import { PaymentMethodsPreview } from '@/components/checkout/payment-methods-preview';
import type {
    CheckoutPaymentMethodId,
    CardToCardTransferDetails,
    OrderSummaryContent,
} from '@/lib/checkout-confirm';
import type { CheckoutOrderContext } from '@/lib/checkout-order';
import { checkoutChapterConfirmUrl } from '@/lib/checkout-urls';
import { cn } from '@/lib/utils';

const cardClassName =
    'flex w-full flex-col items-center gap-4 rounded-[28px] bg-surface px-5 py-6 text-center shadow-soft ring-1 ring-border';

type OrderSummaryCardProps = {
    summary: OrderSummaryContent;
    showChapterSelector: boolean;
    chapterPackages: CatalogPackage[];
    orderContext?: CheckoutOrderContext | null;
    selectedPaymentMethod?: CheckoutPaymentMethodId;
    onSelectPaymentMethod?: (methodId: CheckoutPaymentMethodId) => void;
    paymentProcessing?: boolean;
    cardToCardAvailable?: boolean;
    cardToCardUnavailableMessage?: string | null;
    cardToCardTransfer?: CardToCardTransferDetails | null;
    receiptFile?: File | null;
    onReceiptChange?: (file: File | null) => void;
    receiptError?: string;
};

export function OrderSummaryCard({
    summary,
    showChapterSelector,
    chapterPackages,
    orderContext = null,
    selectedPaymentMethod,
    onSelectPaymentMethod,
    paymentProcessing = false,
    cardToCardAvailable = false,
    cardToCardUnavailableMessage = null,
    cardToCardTransfer = null,
    receiptFile = null,
    onReceiptChange,
    receiptError,
}: OrderSummaryCardProps) {
    const showPaymentMethods =
        orderContext?.payment === 'cash' &&
        !showChapterSelector &&
        selectedPaymentMethod !== undefined &&
        onSelectPaymentMethod !== undefined;
    const [selectedChapterSlug, setSelectedChapterSlug] = useState(
        () => chapterPackages[0]?.slug ?? '',
    );

    const chapterContinueHref = checkoutChapterConfirmUrl(selectedChapterSlug);

    const summaryScrollCtaClassName = cn(
        'mt-1 flex h-11 w-full max-w-[280px] items-center justify-center rounded-pill border border-border bg-surface px-4 text-sm font-bold text-text shadow-soft transition-colors hover:bg-purple-soft',
        showChapterSelector &&
            !selectedChapterSlug &&
            'pointer-events-none opacity-50',
    );

    return (
        <article className={cardClassName}>
            <h2 className="text-base font-bold text-text">{summary.title}</h2>

            <div className="flex w-full flex-col items-center gap-2">
                <p className="text-sm font-bold text-purple">
                    {summary.paymentType}
                </p>

                {summary.priceLine ? (
                    <p className="text-2xl font-black text-text">
                        {summary.priceLine}
                    </p>
                ) : null}

                {summary.mainLine ? (
                    <p className="text-base font-bold text-text">
                        {summary.mainLine}
                    </p>
                ) : null}

                <p className="text-sm font-medium leading-relaxed text-muted">
                    {summary.description}
                </p>
            </div>

            {showChapterSelector ? (
                <div className="w-full pt-1">
                    <ChapterSelector
                        chapterPackages={chapterPackages}
                        selectedSlug={selectedChapterSlug}
                        onSelectSlug={setSelectedChapterSlug}
                    />
                </div>
            ) : null}

            {showChapterSelector ? (
                <Link
                    href={chapterContinueHref}
                    className={summaryScrollCtaClassName}
                    aria-disabled={!selectedChapterSlug || undefined}
                >
                    ادامه با فصل انتخابی
                </Link>
            ) : null}

            {showPaymentMethods ? (
                <PaymentMethodsPreview
                    embedded
                    orderContext={orderContext}
                    selectedPaymentMethod={selectedPaymentMethod}
                    onSelectPaymentMethod={onSelectPaymentMethod}
                    processing={paymentProcessing}
                    cardToCardAvailable={cardToCardAvailable}
                    cardToCardUnavailableMessage={cardToCardUnavailableMessage}
                    cardToCardTransfer={cardToCardTransfer}
                    amountLine={summary.priceLine}
                    receiptFile={receiptFile}
                    onReceiptChange={onReceiptChange}
                    receiptError={receiptError}
                />
            ) : null}
        </article>
    );
}
