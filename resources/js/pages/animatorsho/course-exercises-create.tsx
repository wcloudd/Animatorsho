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
    maxAttachments,
}: CourseExercisesCreatePageProps) {
    const textareaRef = useRef<HTMLTextAreaElement>(null);
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
                                htmlFor="exercise-attachments"
                                className={userLabelClassName}
                            >
                                فایل تمرین
                            </Label>
                            <Input
                                id="exercise-attachments"
                                name="attachments[]"
                                type="file"
                                multiple
                                required
                                onChange={(event) =>
                                    setData(
                                        'attachments',
                                        event.target.files
                                            ? Array.from(event.target.files)
                                            : [],
                                    )
                                }
                                className={userFieldClassName}
                            />
                            <p className="text-xs font-medium text-muted">
                                حداکثر {maxAttachments} فایل، هر فایل تا ۵
                                مگابایت
                            </p>
                            <InputError message={errors.attachments} />
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
