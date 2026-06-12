import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatFileSize } from '@/lib/format-file-size';
import {
    userFieldClassName,
    userLabelClassName,
    userSubmitButtonClassName,
    userTextareaClassName,
} from '@/lib/user-form-styles';
import { useHoneypotField } from '@/hooks/use-honeypot-field';
import { cn } from '@/lib/utils';

type SupportTicketMessageFormProps = {
    action: string;
    submitLabel?: string;
    waitingForUserField?: boolean;
    unstyled?: boolean;
};

export function SupportTicketMessageForm({
    action,
    submitLabel = 'ارسال پاسخ',
    waitingForUserField = false,
    unstyled = false,
}: SupportTicketMessageFormProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        body: '',
        waiting_for_user: false,
        attachment: null as File | null,
    });
    const { field: honeypotField, withHoneypot } = useHoneypotField();

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(action, {
            preserveScroll: true,
            forceFormData: true,
            transform: (formData) => withHoneypot(formData),
            onSuccess: () => reset('body', 'waiting_for_user', 'attachment'),
        });
    };

    return (
        <form
            onSubmit={submit}
            className={cn(
                'relative flex flex-col gap-4',
                !unstyled &&
                    'rounded-[28px] bg-surface px-5 py-5 shadow-soft ring-1 ring-border',
            )}
        >
            {honeypotField}
            <div className="grid gap-2">
                <Label htmlFor="reply-body" className={userLabelClassName}>
                    متن پاسخ
                </Label>
                <textarea
                    id="reply-body"
                    value={data.body}
                    onChange={(event) => setData('body', event.target.value)}
                    rows={4}
                    className={userTextareaClassName}
                />
                {errors.body ? (
                    <p className="text-xs text-red">{errors.body}</p>
                ) : null}
            </div>

            <div className="grid gap-2">
                <Label htmlFor="reply-attachment" className={userLabelClassName}>
                    پیوست (اختیاری)
                </Label>
                <Input
                    id="reply-attachment"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,.pdf,.zip,image/jpeg,image/png,image/webp,application/pdf,application/zip"
                    onChange={(event) =>
                        setData('attachment', event.target.files?.[0] ?? null)
                    }
                    className={userFieldClassName}
                />
                <p className="text-xs text-muted">
                    حداکثر ۵ مگابایت — jpg, png, webp, pdf, zip
                </p>
                {data.attachment ? (
                    <p className="text-xs text-text">
                        {data.attachment.name} (
                        {formatFileSize(data.attachment.size)})
                    </p>
                ) : null}
                {errors.attachment ? (
                    <p className="text-xs text-red">{errors.attachment}</p>
                ) : null}
            </div>

            {waitingForUserField ? (
                <label className="flex items-center gap-2 text-sm text-text">
                    <input
                        type="checkbox"
                        checked={data.waiting_for_user}
                        onChange={(event) =>
                            setData('waiting_for_user', event.target.checked)
                        }
                        className="size-4 rounded border-border text-purple focus:ring-purple"
                    />
                    منتظر پاسخ کاربر
                </label>
            ) : null}

            <button
                type="submit"
                disabled={processing}
                className={userSubmitButtonClassName}
            >
                {submitLabel}
            </button>
        </form>
    );
}
