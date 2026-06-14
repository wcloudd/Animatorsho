import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft, ClipboardList } from 'lucide-react';
import type { FormEvent } from 'react';
import { useRef } from 'react';
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

export default function CourseExercisesCreate({
    storeUrl,
    indexUrl,
    maxAttachmentKb,
}: CourseExercisesCreatePageProps) {
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const { data, setData, post, processing, errors } = useForm<{
        title: string;
        description: string;
        submission_url: string;
        file_path: string;
        attachment: File | null;
    }>({
        title: '',
        description: '',
        submission_url: '',
        file_path: '',
        attachment: null,
    });
    const { field: honeypotField, withHoneypot } = useHoneypotField();

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
                            لینک ویدئو، فایل تمرین یا متن داستان را ارسال کن
                        </p>
                    </header>

                    <form
                        onSubmit={submit}
                        className="relative flex flex-col gap-4 rounded-[28px] bg-surface px-4 py-4 shadow-soft ring-1 ring-border"
                    >
                        {honeypotField}

                        <div className="grid gap-2">
                            <Label htmlFor="exercise-title" className={userLabelClassName}>
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
                            <Label
                                htmlFor="exercise-submission-url"
                                className={userLabelClassName}
                            >
                                لینک تمرین
                            </Label>
                            <Input
                                id="exercise-submission-url"
                                name="submission_url"
                                type="url"
                                dir="ltr"
                                placeholder="https://"
                                value={data.submission_url}
                                onChange={(event) =>
                                    setData('submission_url', event.target.value)
                                }
                                className={userFieldClassName}
                            />
                            <InputError message={errors.submission_url} />
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="exercise-attachment"
                                className={userLabelClassName}
                            >
                                فایل تمرین (اختیاری)
                            </Label>
                            <Input
                                id="exercise-attachment"
                                name="attachment"
                                type="file"
                                onChange={(event) =>
                                    setData(
                                        'attachment',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                                className={userFieldClassName}
                            />
                            <p className="text-xs font-medium text-muted">
                                حداکثر حجم فایل ۵ مگابایت است.
                            </p>
                            <InputError message={errors.attachment} />
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="exercise-file-path"
                                className={userLabelClassName}
                            >
                                مسیر فایل عمومی (اختیاری)
                            </Label>
                            <Input
                                id="exercise-file-path"
                                name="file_path"
                                type="text"
                                dir="ltr"
                                placeholder="https:// یا مسیر عمومی"
                                value={data.file_path}
                                onChange={(event) =>
                                    setData('file_path', event.target.value)
                                }
                                className={userFieldClassName}
                            />
                            <InputError message={errors.file_path} />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
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
