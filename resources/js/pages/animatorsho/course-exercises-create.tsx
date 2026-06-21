import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft, ClipboardList, Paperclip, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { SimpleWritingEditor } from '@/components/course/simple-writing-editor';
import { PageContainer } from '@/components/page-container';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useHoneypotField } from '@/hooks/use-honeypot-field';
import type { CourseExercisesCreatePageProps } from '@/lib/course-exercises-data';
import {
    userFieldClassName,
    userLabelClassName,
    userSubmitButtonClassName,
} from '@/lib/user-form-styles';

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} بایت`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} کیلوبایت`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} مگابایت`;
}

export default function CourseExercisesCreate({
    storeUrl,
    indexUrl,
    maxAttachments,
}: CourseExercisesCreatePageProps) {
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [overLimitError, setOverLimitError] = useState<string | null>(null);

    const { data, setData, post, processing, errors } = useForm<{
        title: string;
        description: string;
        attachments: File[];
    }>({
        title: '',
        description: '',
        attachments: [],
    });
    const { field: honeypotField, withHoneypot } = useHoneypotField();

    const handleFilesAdded = (fileList: FileList | null) => {
        if (!fileList || fileList.length === 0) return;

        const incoming = Array.from(fileList);
        const existing = data.attachments;

        const deduped = incoming.filter(
            (newFile) =>
                !existing.some(
                    (f) =>
                        f.name === newFile.name &&
                        f.size === newFile.size &&
                        f.lastModified === newFile.lastModified,
                ),
        );

        if (existing.length + deduped.length > maxAttachments) {
            setOverLimitError(`حداکثر می‌توانی ${maxAttachments} فایل ارسال کنی.`);
        } else {
            setOverLimitError(null);
            setData('attachments', [...existing, ...deduped]);
        }

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const removeFile = (index: number) => {
        setOverLimitError(null);
        setData(
            'attachments',
            data.attachments.filter((_, i) => i !== index),
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(storeUrl, {
            preserveScroll: true,
            forceFormData: true,
            transform: (formData) => withHoneypot(formData),
        });
    };

    return (
        <>
            <Head title="ارسال تمرین" />
            <PageContainer>
                <div className="flex flex-col gap-5">
                    <Link
                        href={indexUrl}
                        className="inline-flex items-center gap-1 self-start text-xs font-bold text-purple"
                    >
                        <ChevronLeft className="size-4" />
                        بازگشت به تمرین‌ها
                    </Link>

                    <header className="flex flex-col items-center gap-3 text-center">
                        <span className="inline-flex items-center gap-1.5 rounded-pill bg-gold-soft px-3 py-1 text-[11px] font-bold text-gold ring-1 ring-gold/20">
                            <ClipboardList className="size-3.5" />
                            ارسال تمرین
                        </span>
                        <h1 className="font-display text-[1.625rem] leading-tight font-bold text-text">
                            ارسال تمرین جدید
                        </h1>
                        <p className="max-w-[320px] text-sm font-medium leading-relaxed text-muted">
                            فایل تمرین و در صورت نیاز متن داستان را ارسال کن
                        </p>
                    </header>

                    <form
                        onSubmit={submit}
                        className="relative flex flex-col gap-4 rounded-[28px] bg-surface px-4 py-4 shadow-soft ring-1 ring-border"
                    >
                        {honeypotField}

                        <div className="grid gap-2">
                            <Label
                                htmlFor="exercise-title"
                                className={userLabelClassName}
                            >
                                عنوان تمرین
                            </Label>
                            <Input
                                id="exercise-title"
                                name="title"
                                type="text"
                                value={data.title}
                                onChange={(event) =>
                                    setData('title', event.target.value)
                                }
                                className={userFieldClassName}
                            />
                            <InputError message={errors.title} />
                        </div>

                        <SimpleWritingEditor
                            id="exercise-description"
                            label="توضیح تمرین / متن داستان"
                            value={data.description}
                            onChange={(value) => setData('description', value)}
                            error={errors.description}
                            helperText="می‌توانی توضیح تمرین، متن داستان یا نکات لازم را اینجا بنویسی."
                            textareaRef={textareaRef}
                        />

                        <div className="grid gap-2">
                            <Label className={userLabelClassName}>
                                فایل تمرین
                            </Label>

                            <input
                                ref={fileInputRef}
                                type="file"
                                multiple
                                className="sr-only"
                                tabIndex={-1}
                                onChange={(e) =>
                                    handleFilesAdded(e.target.files)
                                }
                            />

                            {data.attachments.length < maxAttachments && (
                                <button
                                    type="button"
                                    onClick={() =>
                                        fileInputRef.current?.click()
                                    }
                                    className="flex h-11 items-center justify-center gap-2 rounded-2xl border border-dashed border-border bg-surface text-sm font-bold text-purple ring-1 ring-border/50 transition-colors hover:border-purple/50 hover:bg-purple-soft"
                                >
                                    <Paperclip className="size-4" />
                                    افزودن فایل
                                </button>
                            )}

                            <p className="text-xs font-medium text-muted">
                                حداکثر {maxAttachments} فایل، هر فایل تا ۵
                                مگابایت
                            </p>

                            {overLimitError !== null && (
                                <p className="text-xs font-medium text-red-500">
                                    {overLimitError}
                                </p>
                            )}

                            <InputError message={errors.attachments} />

                            {data.attachments.length > 0 && (
                                <div className="grid gap-2">
                                    <p className="text-xs font-bold text-text">
                                        فایل‌های انتخاب‌شده
                                    </p>
                                    <ul className="flex flex-col gap-1.5">
                                        {data.attachments.map((file, index) => (
                                            <li
                                                key={`${file.name}-${file.size}-${file.lastModified}`}
                                                className="flex items-center justify-between gap-3 rounded-2xl bg-bg px-3 py-2.5 ring-1 ring-border/70"
                                            >
                                                <span className="flex min-w-0 flex-col gap-0.5">
                                                    <span className="truncate text-xs font-bold text-text">
                                                        {file.name}
                                                    </span>
                                                    <span className="text-[11px] font-medium text-muted">
                                                        {formatFileSize(
                                                            file.size,
                                                        )}
                                                    </span>
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        removeFile(index)
                                                    }
                                                    className="inline-flex shrink-0 items-center gap-1 text-xs font-bold text-red-500 transition-colors hover:text-red-600"
                                                >
                                                    <X className="size-3.5" />
                                                    حذف
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={
                                processing || data.attachments.length === 0
                            }
                            className={userSubmitButtonClassName}
                        >
                            {processing ? 'در حال ارسال...' : 'ارسال تمرین'}
                        </button>
                    </form>
                </div>
            </PageContainer>
        </>
    );
}
