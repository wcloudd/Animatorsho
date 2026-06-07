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

type AdminInstallmentReviewPanelProps = {
    payment: AdminPaymentListItem;
};

export function AdminInstallmentReviewPanel({
    payment,
}: AdminInstallmentReviewPanelProps) {
    const [showRejectForm, setShowRejectForm] = useState(false);
    const [confirmKey, setConfirmKey] = useState<string | number | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        note: '',
    });

    return (
        <AdminActionRow
            bordered={false}
            className="rounded-xl bg-purple-soft/70 p-3 ring-1 ring-purple/25"
        >
            <p className="text-xs font-bold text-purple">
                بررسی درخواست خرید اقساطی
            </p>

            <p className="text-xs leading-5 text-muted">
                تأیید یعنی پرداخت/شرایط توافق‌شده به‌صورت دستی تأیید شده و
                سفارش پرداخت‌شده ثبت می‌شود. لایسنس SpotPlayer در وضعیت
                «در انتظار فعال‌سازی» ایجاد می‌شود.
            </p>

            {payment.installmentRequestedTerm ? (
                <p className="text-sm text-text">
                    <span className="font-medium text-muted">مدت اقساط: </span>
                    {payment.installmentRequestedTerm}
                </p>
            ) : null}

            {payment.installmentNote ? (
                <p className="text-sm text-text">
                    <span className="font-medium text-muted">توضیح کاربر: </span>
                    {payment.installmentNote}
                </p>
            ) : null}

            <div className="flex flex-wrap gap-2">
                {payment.canApprove ? (
                    <AdminConfirmAction
                        actionKey={`approve-${payment.id}`}
                        activeKey={confirmKey}
                        onActivate={setConfirmKey}
                        onCancel={() => setConfirmKey(null)}
                        triggerLabel="تأیید درخواست"
                        confirmLabel="تأیید درخواست"
                        message="دسترسی قسطی فعال می‌شود."
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
                        رد درخواست
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
                    <Label htmlFor={`installment_reject_note_${payment.id}`}>
                        دلیل رد (اختیاری)
                    </Label>
                    <textarea
                        id={`installment_reject_note_${payment.id}`}
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
                        تأیید رد درخواست
                    </AdminButton>
                </form>
            ) : null}
        </AdminActionRow>
    );
}
