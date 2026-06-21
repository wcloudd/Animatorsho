import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useRef, useState } from 'react';
import { AdminActionRow } from '@/components/admin/admin-action-row';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminConfirmAction } from '@/components/admin/admin-confirm-action';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import { AdminStatusBadge } from '@/components/admin/admin-status-badge';
import { SafeStoryText } from '@/components/course/safe-story-text';
import InputError from '@/components/input-error';
import { surfaceCardClassName } from '@/components/page-container';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    AdminExerciseFeedbackAttachment,
    AdminExerciseSubmissionDetail,
    AdminStatusOption,
} from '@/types/admin';
import { cn } from '@/lib/utils';

const textareaClassName = cn(
    'border-input bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm text-start shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
    'min-h-[120px]',
);

type PageProps = {
    submission: AdminExerciseSubmissionDetail;
    statusOptions: AdminStatusOption[];
    maxFeedbackAttachments: number;
};

export default function AdminExerciseSubmissionShow({
    submission,
    statusOptions,
    maxFeedbackAttachments,
}: PageProps) {
    const [confirmKey, setConfirmKey] = useState<string | number | null>(null);
    const { data, setData, patch, processing, errors } = useForm({
        status: submission.statusValue,
        admin_feedback: submission.adminFeedback ?? '',
    });

    const feedbackFileInputRef = useRef<HTMLInputElement>(null);
    const [feedbackFiles, setFeedbackFiles] = useState<File[]>([]);
    const [feedbackUploading, setFeedbackUploading] = useState(false);

    const activeFeedbackCount = submission.feedbackAttachments.filter(
        (a: AdminExerciseFeedbackAttachment) => !a.isDeleted,
    ).length;
    const remainingSlots = maxFeedbackAttachments - activeFeedbackCount;

    const handleFeedbackFilesChange = (
        e: React.ChangeEvent<HTMLInputElement>,
    ) => {
        setFeedbackFiles(Array.from(e.target.files ?? []).slice(0, remainingSlots));
    };

    const submitFeedbackUpload = (e: FormEvent) => {
        e.preventDefault();
        if (feedbackFiles.length === 0) return;

        const formData = new FormData();
        feedbackFiles.forEach((f) => formData.append('feedback_files[]', f));

        setFeedbackUploading(true);
        router.post(
            `/admin/exercise-submissions/${submission.id}/feedback-attachments`,
            formData,
            {
                onFinish: () => {
                    setFeedbackUploading(false);
                    setFeedbackFiles([]);
                    if (feedbackFileInputRef.current) {
                        feedbackFileInputRef.current.value = '';
                    }
                },
                preserveScroll: true,
            },
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(`/admin/exercise-submissions/${submission.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`بررسی تمرین — ${submission.title}`} />
            <AdminPageHeader
                title={submission.title}
                description={`${submission.studentName} · ${submission.studentMobile ?? '—'}`}
                actions={
                    <AdminButton asChild size="sm" adminVariant="outline">
                        <Link href="/admin/exercise-submissions">
                            بازگشت به لیست
                        </Link>
                    </AdminButton>
                }
            />

            <div
                className={cn(
                    surfaceCardClassName,
                    'mb-4 flex flex-col gap-4 p-4 sm:p-5',
                )}
            >
                <div className="flex flex-wrap items-center gap-2">
                    <AdminStatusBadge tone={submission.statusTone}>
                        {submission.status}
                    </AdminStatusBadge>
                </div>

                <AdminInfoGrid>
                    <AdminDetailRow
                        label="نام هنرجو"
                        value={submission.studentName}
                    />
                    <AdminDetailRow
                        label="موبایل"
                        value={submission.studentMobile ?? '—'}
                    />
                    <AdminDetailRow
                        label="تاریخ ارسال"
                        value={submission.submittedAtLabel}
                    />
                    <AdminDetailRow
                        label="آخرین بررسی"
                        value={submission.reviewedAtLabel}
                    />
                    <AdminDetailRow
                        label="بررسی‌کننده"
                        value={submission.reviewedByName ?? '—'}
                    />
                </AdminInfoGrid>

                {submission.descriptionHtml ? (
                    <div className="flex flex-col gap-2">
                        <AdminSectionTitle className="mb-0">
                            توضیحات هنرجو
                        </AdminSectionTitle>
                        <SafeStoryText
                            html={submission.descriptionHtml}
                            className="text-sm leading-relaxed text-muted [&_p]:mb-2 [&_ul]:list-disc [&_ul]:ps-5 [&_ol]:list-decimal [&_ol]:ps-5"
                        />
                    </div>
                ) : null}

                <div className="flex flex-col gap-2">
                    <AdminSectionTitle className="mb-0">
                        ارسال تمرین
                    </AdminSectionTitle>
                    {submission.submissionLink ? (
                        <a
                            href={submission.submissionLink}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-sm font-bold text-purple"
                        >
                            {submission.submissionLinkLabel}
                        </a>
                    ) : (
                        <p className="text-sm text-muted">
                            {submission.filePathNote ??
                                'لینک عمومی برای این ارسال ثبت نشده است.'}
                        </p>
                    )}
                </div>

                {submission.attachments.length > 0 ? (
                    <div className="flex flex-col gap-3 rounded-2xl border border-[#e8e0f0] bg-bg p-4">
                        <AdminSectionTitle className="mb-0">
                            فایل‌های آپلودی
                        </AdminSectionTitle>

                        <ul className="flex flex-col gap-3">
                            {submission.attachments.map((attachment) => (
                                <li
                                    key={
                                        attachment.id ??
                                        `legacy-${attachment.originalName}`
                                    }
                                    className="flex flex-col gap-3 rounded-xl border border-[#e8e0f0] bg-surface p-3"
                                >
                                    {attachment.isDeleted ? (
                                        <p className="text-sm font-medium text-muted">
                                            {attachment.originalName} — فایل
                                            حذف شده است
                                        </p>
                                    ) : (
                                        <>
                                            <AdminInfoGrid>
                                                <AdminDetailRow
                                                    label="نام فایل"
                                                    value={
                                                        attachment.originalName
                                                    }
                                                />
                                                <AdminDetailRow
                                                    label="حجم"
                                                    value={
                                                        attachment.sizeLabel
                                                    }
                                                />
                                                <AdminDetailRow
                                                    label="نوع"
                                                    value={
                                                        attachment.mimeType
                                                    }
                                                />
                                                <AdminDetailRow
                                                    label="پسوند"
                                                    value={
                                                        attachment.extension
                                                    }
                                                />
                                            </AdminInfoGrid>

                                            <AdminActionRow>
                                                <AdminButton
                                                    asChild
                                                    size="sm"
                                                    adminVariant="outline"
                                                >
                                                    <a
                                                        href={
                                                            attachment.downloadUrl
                                                        }
                                                    >
                                                        دانلود فایل
                                                    </a>
                                                </AdminButton>
                                                {attachment.deleteUrl ? (
                                                    <AdminConfirmAction
                                                        actionKey={
                                                            attachment.id ??
                                                            attachment.originalName
                                                        }
                                                        activeKey={confirmKey}
                                                        onActivate={
                                                            setConfirmKey
                                                        }
                                                        onCancel={() =>
                                                            setConfirmKey(null)
                                                        }
                                                        triggerLabel="حذف فایل"
                                                        confirmLabel="تأیید حذف"
                                                        message="فایل از فضای ذخیره‌سازی حذف می‌شود اما رکورد تمرین باقی می‌ماند."
                                                        href={
                                                            attachment.deleteUrl
                                                        }
                                                        method="delete"
                                                    />
                                                ) : null}
                                            </AdminActionRow>
                                        </>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                ) : submission.attachment ? (
                    <div className="flex flex-col gap-3 rounded-2xl border border-[#e8e0f0] bg-bg p-4">
                        <AdminSectionTitle className="mb-0">
                            فایل آپلودی
                        </AdminSectionTitle>

                        {submission.attachment.isDeleted ? (
                            <p className="text-sm font-medium text-muted">
                                فایل حذف شده است
                            </p>
                        ) : (
                            <>
                                <AdminInfoGrid>
                                    <AdminDetailRow
                                        label="نام فایل"
                                        value={
                                            submission.attachment.originalName
                                        }
                                    />
                                    <AdminDetailRow
                                        label="حجم"
                                        value={submission.attachment.sizeLabel}
                                    />
                                    <AdminDetailRow
                                        label="نوع"
                                        value={submission.attachment.mimeType}
                                    />
                                    <AdminDetailRow
                                        label="پسوند"
                                        value={submission.attachment.extension}
                                    />
                                </AdminInfoGrid>

                                <AdminActionRow>
                                    <AdminButton
                                        asChild
                                        size="sm"
                                        adminVariant="outline"
                                    >
                                        <a
                                            href={
                                                submission.attachment
                                                    .downloadUrl
                                            }
                                        >
                                            دانلود فایل
                                        </a>
                                    </AdminButton>
                                    <AdminConfirmAction
                                        actionKey="delete-attachment"
                                        activeKey={confirmKey}
                                        onActivate={setConfirmKey}
                                        onCancel={() => setConfirmKey(null)}
                                        triggerLabel="حذف فایل"
                                        confirmLabel="تأیید حذف"
                                        message="فایل از فضای ذخیره‌سازی حذف می‌شود اما رکورد تمرین باقی می‌ماند."
                                        href={`/admin/exercise-submissions/${submission.id}/attachment`}
                                        method="delete"
                                    />
                                </AdminActionRow>
                            </>
                        )}
                    </div>
                ) : null}
            </div>

            <div
                className={cn(
                    surfaceCardClassName,
                    'mb-4 flex flex-col gap-4 p-4 sm:p-5',
                )}
            >
                <AdminSectionTitle className="mb-0">
                    فایل‌های استاد برای هنرجو
                </AdminSectionTitle>

                {submission.feedbackAttachments.length > 0 ? (
                    <ul className="flex flex-col gap-3">
                        {submission.feedbackAttachments.map((attachment) => (
                            <li
                                key={attachment.id}
                                className="flex flex-col gap-3 rounded-xl border border-[#e8e0f0] bg-surface p-3"
                            >
                                {attachment.isDeleted ? (
                                    <p className="text-sm font-medium text-muted">
                                        {attachment.originalName} — حذف شده
                                    </p>
                                ) : (
                                    <>
                                        <AdminInfoGrid>
                                            <AdminDetailRow
                                                label="نام فایل"
                                                value={attachment.originalName}
                                            />
                                            <AdminDetailRow
                                                label="حجم"
                                                value={attachment.sizeLabel}
                                            />
                                            <AdminDetailRow
                                                label="پسوند"
                                                value={attachment.extension}
                                            />
                                        </AdminInfoGrid>
                                        <AdminActionRow>
                                            <AdminButton
                                                asChild
                                                size="sm"
                                                adminVariant="outline"
                                            >
                                                <a href={attachment.downloadUrl}>
                                                    دانلود
                                                </a>
                                            </AdminButton>
                                            {attachment.deleteUrl ? (
                                                <AdminConfirmAction
                                                    actionKey={`feedback-${attachment.id}`}
                                                    activeKey={confirmKey}
                                                    onActivate={setConfirmKey}
                                                    onCancel={() =>
                                                        setConfirmKey(null)
                                                    }
                                                    triggerLabel="حذف فایل"
                                                    confirmLabel="تأیید حذف"
                                                    message="فایل استاد از فضای ذخیره‌سازی حذف می‌شود."
                                                    href={attachment.deleteUrl}
                                                    method="delete"
                                                />
                                            ) : null}
                                        </AdminActionRow>
                                    </>
                                )}
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="text-sm font-medium text-muted">
                        هنوز فایلی برای هنرجو آپلود نشده است.
                    </p>
                )}

                {remainingSlots > 0 ? (
                    <form
                        onSubmit={submitFeedbackUpload}
                        className="flex flex-col gap-3"
                    >
                        <input
                            ref={feedbackFileInputRef}
                            type="file"
                            multiple
                            id="feedback-file-input"
                            className="hidden"
                            onChange={handleFeedbackFilesChange}
                        />
                        <label
                            htmlFor="feedback-file-input"
                            className="inline-flex cursor-pointer items-center justify-center rounded-lg border border-dashed border-[#c4b5d9] bg-bg px-4 py-3 text-sm font-bold text-purple transition-colors hover:bg-purple/5"
                        >
                            انتخاب فایل‌ها
                            <span className="mr-1 text-xs font-medium text-muted">
                                ({remainingSlots} جای خالی)
                            </span>
                        </label>
                        {feedbackFiles.length > 0 ? (
                            <ul className="flex flex-col gap-1">
                                {feedbackFiles.map((f, i) => (
                                    <li
                                        key={i}
                                        className="text-xs font-medium text-muted"
                                    >
                                        {f.name}
                                    </li>
                                ))}
                            </ul>
                        ) : null}
                        {feedbackFiles.length > 0 ? (
                            <AdminButton
                                type="submit"
                                size="sm"
                                adminVariant="brand"
                                disabled={feedbackUploading}
                            >
                                آپلود فایل‌های استاد
                            </AdminButton>
                        ) : null}
                    </form>
                ) : null}
            </div>

            <form
                onSubmit={submit}
                className={cn(
                    surfaceCardClassName,
                    'flex flex-col gap-4 p-4 sm:p-5',
                )}
            >
                <AdminSectionTitle className="mb-0">
                    بررسی و بازخورد
                </AdminSectionTitle>

                <div className="grid gap-2">
                    <Label htmlFor="exercise-submission-status">وضعیت</Label>
                    <Select
                        value={data.status}
                        onValueChange={(value) => setData('status', value)}
                    >
                        <SelectTrigger
                            id="exercise-submission-status"
                            className="h-10 w-full"
                        >
                            <SelectValue placeholder="انتخاب وضعیت" />
                        </SelectTrigger>
                        <SelectContent position="popper">
                            {statusOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="exercise-submission-feedback">
                        بازخورد استاد
                    </Label>
                    <textarea
                        id="exercise-submission-feedback"
                        value={data.admin_feedback}
                        onChange={(event) =>
                            setData('admin_feedback', event.target.value)
                        }
                        rows={5}
                        className={textareaClassName}
                    />
                    <InputError message={errors.admin_feedback} />
                </div>

                <AdminButton
                    type="submit"
                    size="sm"
                    adminVariant="brand"
                    disabled={processing}
                >
                    ذخیره بررسی
                </AdminButton>
            </form>
        </>
    );
}
