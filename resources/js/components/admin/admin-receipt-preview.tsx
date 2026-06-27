import { AdminButton } from '@/components/admin/admin-button';

type AdminReceiptPreviewProps = {
    receiptUrl: string | null;
    orderNumber: string;
    label?: string;
    alt?: string;
};

/**
 * Shared admin receipt preview (image + protected download link). Used for both
 * full-payment card-to-card receipts and installment down-payment card-to-card
 * receipts so they display identically. The receipt URL points at the
 * admin-only protected stream route; nothing here exposes the file publicly.
 */
export function AdminReceiptPreview({
    receiptUrl,
    orderNumber,
    label = 'رسید پرداخت',
    alt,
}: AdminReceiptPreviewProps) {
    if (!receiptUrl) {
        return null;
    }

    return (
        <div className="flex flex-col gap-2">
            <span className="text-xs font-medium text-muted">{label}</span>
            <a
                href={receiptUrl}
                target="_blank"
                rel="noreferrer"
                className="overflow-hidden rounded-xl ring-1 ring-[#e8e0f0]"
            >
                <img
                    src={receiptUrl}
                    alt={alt ?? `رسید سفارش ${orderNumber}`}
                    className="max-h-48 w-full bg-bg object-contain"
                />
            </a>
            <AdminButton asChild size="sm" adminVariant="outline">
                <a href={receiptUrl} target="_blank" rel="noreferrer">
                    مشاهده رسید
                </a>
            </AdminButton>
        </div>
    );
}
