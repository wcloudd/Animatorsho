import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    CARD_TO_CARD_INSTRUCTIONS,
    type CardToCardTransferDetails,
} from '@/lib/checkout-confirm';
import { cn } from '@/lib/utils';

type CardToCardDetailsPanelProps = {
    transferDetails: CardToCardTransferDetails;
    amountLine: string | null | undefined;
    receiptFile: File | null;
    onReceiptChange: (file: File | null) => void;
    receiptError?: string;
    processing?: boolean;
};

export function CardToCardDetailsPanel({
    transferDetails,
    amountLine,
    receiptFile,
    onReceiptChange,
    receiptError,
    processing = false,
}: CardToCardDetailsPanelProps) {
    return (
        <div className="flex w-full flex-col gap-4 rounded-2xl bg-bg px-4 py-4 ring-1 ring-border">
            {amountLine ? (
                <div className="grid gap-1.5">
                    <span className="text-xs font-medium text-muted">
                        مبلغ قابل واریز
                    </span>
                    <p className="rounded-xl bg-surface px-3 py-2.5 text-center text-lg font-black text-text ring-1 ring-border">
                        {amountLine}
                    </p>
                </div>
            ) : null}

            <div className="grid gap-3">
                <div className="grid gap-1.5">
                    <span className="text-xs font-medium text-muted">
                        شماره کارت
                    </span>
                    <p
                        dir="ltr"
                        className="rounded-xl bg-surface px-3 py-2.5 text-center text-sm font-bold tracking-wide text-text ring-1 ring-border"
                    >
                        {transferDetails.cardNumber}
                    </p>
                </div>

                <div className="grid gap-1.5">
                    <span className="text-xs font-medium text-muted">
                        به نام
                    </span>
                    <p className="rounded-xl bg-surface px-3 py-2.5 text-center text-sm font-bold text-text ring-1 ring-border">
                        {transferDetails.cardOwnerName}
                    </p>
                </div>
            </div>

            <ul className="grid gap-1.5 text-xs font-medium leading-relaxed text-muted">
                {CARD_TO_CARD_INSTRUCTIONS.map((instruction) => (
                    <li key={instruction} className="flex gap-2">
                        <span aria-hidden>•</span>
                        <span>{instruction}</span>
                    </li>
                ))}
            </ul>

            <div className="grid gap-2">
                <Label htmlFor="checkout-receipt-image">تصویر رسید</Label>
                <Input
                    id="checkout-receipt-image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                    className="text-start file:me-3 file:rounded-lg file:border-0 file:bg-purple-soft file:px-3 file:py-1 file:text-xs file:font-bold file:text-purple"
                    onChange={(event) => {
                        const file = event.target.files?.[0] ?? null;
                        onReceiptChange(file);
                    }}
                />
                {receiptFile ? (
                    <p className="text-xs font-medium text-muted">
                        فایل انتخاب‌شده: {receiptFile.name}
                    </p>
                ) : null}
                {receiptError ? (
                    <p className="text-xs font-medium text-red">
                        {receiptError}
                    </p>
                ) : null}
            </div>

            <button
                type="submit"
                disabled={processing}
                className={cn(
                    'btn-cta-green flex h-12 w-full items-center justify-center gap-2 rounded-pill text-base font-bold text-white shadow-soft disabled:opacity-70',
                )}
            >
                {processing ? (
                    <>
                        <Spinner className="size-4" />
                        در حال ارسال رسید...
                    </>
                ) : (
                    'ارسال رسید'
                )}
            </button>
        </div>
    );
}
