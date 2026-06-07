import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect } from 'react';
import { ProfileSectionCard } from '@/components/profile/profile-section-card';
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
import {
    userFieldClassName,
    userLabelClassName,
    userSelectContentClassName,
    userSelectItemClassName,
    userSelectTriggerClassName,
    userSubmitButtonClassName,
    userTextareaClassName,
} from '@/lib/user-form-styles';
import support from '@/routes/support';

type SupportNewTicketFormProps = {
    categoryOptions: SupportCategoryOption[];
    category: string;
    onCategoryChange: (category: string) => void;
};

export function SupportNewTicketForm({
    categoryOptions,
    category,
    onCategoryChange,
}: SupportNewTicketFormProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        subject: '',
        category: categoryOptions[0]?.value ?? '',
        message: '',
        attachment: null as File | null,
    });

    useEffect(() => {
        setData('category', category);
    }, [category, setData]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(support.tickets.store.url(), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <section
            id="new-ticket"
            className="scroll-mt-24"
            aria-labelledby="new-ticket-heading"
        >
            <ProfileSectionCard
                title="ارسال پیام جدید"
                description="موضوع، دسته‌بندی و متن پیام را وارد کن."
            >
                <form onSubmit={submit} className="flex flex-col gap-4">
                    <div className="grid gap-2">
                        <Label
                            htmlFor="ticket-subject"
                            className={userLabelClassName}
                        >
                            موضوع پیام
                        </Label>
                        <Input
                            id="ticket-subject"
                            name="subject"
                            type="text"
                            value={data.subject}
                            onChange={(event) =>
                                setData('subject', event.target.value)
                            }
                            className={userFieldClassName}
                        />
                        {errors.subject ? (
                            <p className="text-xs text-red">{errors.subject}</p>
                        ) : null}
                    </div>

                    <div className="grid gap-2">
                        <Label
                            htmlFor="ticket-category"
                            className={userLabelClassName}
                        >
                            دسته‌بندی
                        </Label>
                        <Select
                            value={data.category}
                            onValueChange={(value) => {
                                setData('category', value);
                                onCategoryChange(value);
                            }}
                        >
                            <SelectTrigger
                                id="ticket-category"
                                className={userSelectTriggerClassName}
                            >
                                <SelectValue placeholder="انتخاب دسته‌بندی" />
                            </SelectTrigger>
                            <SelectContent
                                className={userSelectContentClassName}
                                position="popper"
                            >
                                {categoryOptions.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                        className={userSelectItemClassName}
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
                        <Label
                            htmlFor="ticket-message"
                            className={userLabelClassName}
                        >
                            متن پیام
                        </Label>
                        <textarea
                            id="ticket-message"
                            name="message"
                            rows={5}
                            value={data.message}
                            onChange={(event) =>
                                setData('message', event.target.value)
                            }
                            className={userTextareaClassName}
                        />
                        {errors.message ? (
                            <p className="text-xs text-red">{errors.message}</p>
                        ) : null}
                    </div>

                    <div className="grid gap-2">
                        <Label
                            htmlFor="ticket-attachment"
                            className={userLabelClassName}
                        >
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
                            <p className="text-xs text-red">
                                {errors.attachment}
                            </p>
                        ) : null}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className={userSubmitButtonClassName}
                    >
                        ارسال پیام
                    </button>
                </form>
            </ProfileSectionCard>
        </section>
    );
}
