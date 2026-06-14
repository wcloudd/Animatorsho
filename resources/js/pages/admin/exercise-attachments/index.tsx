import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCommerceCard } from '@/components/admin/admin-commerce-card';
import { AdminConfirmAction } from '@/components/admin/admin-confirm-action';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminPagination } from '@/components/admin/admin-pagination';
import type {
    AdminExerciseAttachmentListItem,
    AdminPaginated,
} from '@/types/admin';

type PageProps = {
    summary: {
        totalCount: number;
        totalSizeBytes: number;
        totalSizeLabel: string;
    };
    attachments: AdminPaginated<AdminExerciseAttachmentListItem>;
};

export default function AdminExerciseAttachmentsIndex({
    summary,
    attachments,
}: PageProps) {
    const [confirmId, setConfirmId] = useState<number | null>(null);

    return (
        <>
            <Head title="فایل‌های تمرین" />
            <AdminPageHeader
                title="فایل‌های تمرین"
                description="مدیریت فضای ذخیره‌سازی فایل‌های ارسالی هنرجویان"
            />

            <div className="mb-4 grid gap-3 sm:grid-cols-2">
                <div className="rounded-2xl border border-[#e8e0f0] bg-surface px-4 py-4">
                    <p className="text-xs font-bold text-muted">تعداد فایل‌ها</p>
                    <p className="mt-1 text-lg font-bold text-text">
                        {summary.totalCount}
                    </p>
                </div>
                <div className="rounded-2xl border border-[#e8e0f0] bg-surface px-4 py-4">
                    <p className="text-xs font-bold text-muted">حجم کل</p>
                    <p className="mt-1 text-lg font-bold text-text">
                        {summary.totalSizeLabel}
                    </p>
                </div>
            </div>

            <div className="flex flex-col gap-3">
                {attachments.data.map((item) => (
                    <AdminCommerceCard
                        key={item.id}
                        title={item.originalName}
                        subtitle={`${item.studentName} · ${item.submissionTitle}`}
                    >
                        <AdminInfoGrid>
                            <AdminDetailRow
                                label="نام هنرجو"
                                value={item.studentName}
                            />
                            <AdminDetailRow
                                label="موبایل"
                                value={item.studentMobile ?? '—'}
                            />
                            <AdminDetailRow
                                label="عنوان تمرین"
                                value={item.submissionTitle}
                            />
                            <AdminDetailRow label="حجم" value={item.sizeLabel} />
                            <AdminDetailRow
                                label="پسوند"
                                value={item.extension}
                            />
                            <AdminDetailRow
                                label="تاریخ ارسال"
                                value={item.uploadedAtLabel}
                            />
                        </AdminInfoGrid>

                        <div className="flex flex-wrap gap-2">
                            <AdminButton asChild size="sm" adminVariant="outline">
                                <a href={item.downloadUrl}>دانلود</a>
                            </AdminButton>
                            <AdminButton asChild size="sm" adminVariant="outline">
                                <Link href={item.reviewUrl}>بررسی تمرین</Link>
                            </AdminButton>
                            <AdminConfirmAction
                                actionKey={item.id}
                                activeKey={confirmId}
                                onActivate={setConfirmId}
                                onCancel={() => setConfirmId(null)}
                                triggerLabel="حذف فایل"
                                confirmLabel="تأیید حذف"
                                message="فایل از فضای ذخیره‌سازی حذف می‌شود اما رکورد تمرین باقی می‌ماند."
                                href={item.deleteUrl}
                                method="delete"
                            />
                        </div>
                    </AdminCommerceCard>
                ))}

                {attachments.data.length === 0 ? (
                    <AdminEmptyState message="فایل آپلودی فعالی ثبت نشده است." />
                ) : null}
            </div>

            <AdminPagination paginator={attachments} />
        </>
    );
}
