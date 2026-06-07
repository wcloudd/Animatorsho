import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { SupportCategoryOption } from '@/types/support';
import { formatFileSize } from '@/lib/format-file-size';
import { cn } from '@/lib/utils';

const fieldClassName =
    'border-input bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm text-start shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

const textareaClassName = cn(fieldClassName, 'min-h-[120px]');

const selectTriggerClassName = cn(
    'h-10 w-full border-border bg-surface text-text shadow-xs',
    'dark:border-border dark:bg-surface dark:text-text dark:hover:bg-surface',
);

const selectContentClassName = cn(
    'border-border bg-surface text-text',
    'dark:border-border dark:bg-surface dark:text-text',
);

const selectItemClassName = cn(
    'text-text focus:bg-purple-soft focus:text-text',
    'dark:text-text dark:focus:bg-purple-soft',
);

type SupportNewTicketFormProps = {
    categoryOptions: SupportCategoryOption[];
    storeUrl: string;
};

export function SupportNewTicketForm({
    categoryOptions,
    storeUrl,
}: SupportNewTicketFormProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        subject: '',
        category: categoryOptions[0]?.value ?? '',
        message: '',
        attachment: null as File | null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(storeUrl, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <section
            id="new-ticket"
            className="scroll-mt-24 flex w-full flex-col gap-4"
            aria-labelledby="new-ticket-heading"
        >
            <h2
                id="new-ticket-heading"
                className="text-base font-bold text-text"
            >
                ارسال پیام جدید
            </h2>

            <form
                onSubmit={submit}
                className="flex flex-col gap-4 rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border"
            >
                <div className="grid gap-2">
                    <Label htmlFor="ticket-subject">موضوع پیام</Label>
                    <Input
                        id="ticket-subject"
                        name="subject"
                        type="text"
                        value={data.subject}
                        onChange={(event) =>
                            setData('subject', event.target.value)
                        }
                        className="bg-surface text-text text-start"
                    />
                    {errors.subject ? (
                        <p className="text-xs text-red">{errors.subject}</p>
                    ) : null}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="ticket-category">دسته‌بندی</Label>
                    <Select
                        value={data.category}
                        onValueChange={(value) => setData('category', value)}
                    >
                        <SelectTrigger
                            id="ticket-category"
                            className={selectTriggerClassName}
                        >
                            <SelectValue placeholder="انتخاب دسته‌بندی" />
                        </SelectTrigger>
                        <SelectContent
                            className={selectContentClassName}
                            position="popper"
                        >
                            {categoryOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                    className={selectItemClassName}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.category ? (
                        <p className="text-xs text-red">{errors.category}</p>
                    ) : null}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="ticket-message">متن پیام</Label>
                    <textarea
                        id="ticket-message"
                        name="message"
                        rows={5}
                        value={data.message}
                        onChange={(event) =>
                            setData('message', event.target.value)
                        }
                        className={textareaClassName}
                    />
                    {errors.message ? (
                        <p className="text-xs text-red">{errors.message}</p>
                    ) : null}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="ticket-attachment">
                        پیوست (اختیاری)
                    </Label>
                    <Input
                        id="ticket-attachment"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.pdf,.zip,image/jpeg,image/png,image/webp,application/pdf,application/zip"
                        onChange={(event) =>
                            setData(
                                'attachment',
                                event.target.files?.[0] ?? null,
                            )
                        }
                        className="bg-surface text-text"
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

                <button
                    type="submit"
                    disabled={processing}
                    className="btn-cta-green flex h-12 w-full items-center justify-center rounded-pill text-sm font-bold text-white disabled:opacity-60"
                >
                    ارسال پیام
                </button>
            </form>
        </section>
    );
}
