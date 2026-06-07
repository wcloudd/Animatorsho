import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AdminActionRow } from '@/components/admin/admin-action-row';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminConfirmAction } from '@/components/admin/admin-confirm-action';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import type { AdminPaymentListItem } from '@/types/admin';
import { cn } from '@/lib/utils';
import { adminCalloutStyles } from '@/components/admin/admin-callout';

const rejectNoteClassName = cn(
    'flex w-full rounded-md border border-[#e8e0f0] bg-surface px-3 py-2 text-sm text-start text-text shadow-xs outline-none placeholder:text-muted focus-visible:ring-[3px] focus-visible:ring-purple/30 disabled:cursor-not-allowed disabled:opacity-50',
    'min-h-[80px]',
);

type AdminPaymentReviewPanelProps = {
    payment: AdminPaymentListItem;
};

export function AdminPaymentReviewPanel({
    payment,
}: AdminPaymentReviewPanelProps) {
    const [showRejectForm, setShowRejectForm] = useState(false);
    const [confirmKey, setConfirmKey] = useState<string | number | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        note: '',
    });

    return (
        <AdminActionRow
            bordered={false}
            className="rounded-xl bg-gold-soft/60 p-3 ring-1 ring-gold/25"
        >
            <p className="text-xs font-bold text-gold">بررسی رسید کارت‌به‌کارت</p>

            {payment.receiptUrl ? (
                <div className="flex flex-col gap-2">
                    <span className="text-xs font-medium text-muted">
                        رسید پرداخت
                    </span>
                    <a
                        href={payment.receiptUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="overflow-hidden rounded-xl ring-1 ring-[#e8e0f0]"
                    >
                        <img
                            src={payment.receiptUrl}
                            alt={`رسید سفارش ${payment.orderNumber}`}
                            className="max-h-48 w-full bg-bg object-contain"
                        />
                    </a>
                    <AdminButton asChild size="sm" adminVariant="outline">
                        <a
                            href={payment.receiptUrl}
                            target="_blank"
                            rel="noreferrer"
                        >
                            مشاهده رسید
                        </a>
                    </AdminButton>
                </div>
            ) : null}

            <div className="flex flex-wrap gap-2">
                {payment.canApprove ? (
                    <AdminConfirmAction
                        actionKey={`approve-${payment.id}`}
                        activeKey={confirmKey}
                        onActivate={setConfirmKey}
                        onCancel={() => setConfirmKey(null)}
                        triggerLabel="تأیید رسید"
                        confirmLabel="تأیید رسید"
                        message="دسترسی کاربر فعال می‌شود."
                        href={`/admin/payments/${payment.id}/approve`}
                        triggerVariant="success"
                        confirmVariant="success"
                    />
                ) : null}

                {payment.canReject ? (
                    <AdminButton
                        type="button"
                        size="sm"
                        adminVariant="dangerOutline"
                        onClick={() => setShowRejectForm((current) => !current)}
                    >
                        رد رسید
                    </AdminButton>
                ) : null}
            </div>

            {showRejectForm && payment.canReject ? (
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        post(`/admin/payments/${payment.id}/reject`, {
                            preserveScroll: true,
                            onSuccess: () => {
                                reset();
                                setShowRejectForm(false);
                            },
                        });
                    }}
                    className={cn(
                        'flex flex-col gap-2 p-3',
                        adminCalloutStyles.error.box,
                    )}
                >
                    <Label htmlFor={`reject_note_${payment.id}`}>
                        دلیل رد (اختیاری)
                    </Label>
                    <textarea
                        id={`reject_note_${payment.id}`}
                        value={data.note}
                        onChange={(event) =>
                            setData('note', event.target.value)
                        }
                        rows={3}
                        className={rejectNoteClassName}
                        placeholder="در صورت نیاز توضیحی برای تیم یا کاربر بنویسید"
                    />
                    <InputError message={errors.note} />
                    <AdminButton
                        type="submit"
                        size="sm"
                        adminVariant="danger"
                        disabled={processing}
                    >
                        تأیید رد رسید
                    </AdminButton>
                </form>
            ) : null}
        </AdminActionRow>
    );
}
