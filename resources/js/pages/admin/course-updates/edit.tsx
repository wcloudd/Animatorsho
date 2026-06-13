import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminCourseUpdateForm } from '@/components/admin/admin-course-update-form';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { surfaceCardClassName } from '@/components/page-container';
import type {
    AdminCourseUpdateFormItem,
    AdminCourseUpdateFormOptions,
} from '@/types/admin';

type PageProps = {
    update: AdminCourseUpdateFormItem & { id: number };
    formOptions: AdminCourseUpdateFormOptions;
};

export default function AdminCourseUpdatesEdit({
    update,
    formOptions,
}: PageProps) {
    const { data, setData, patch, processing, errors } = useForm({
        title: update.title,
        summary: update.summary,
        body: update.body,
        type: update.type,
        visual_theme: update.visualTheme,
        status: update.status,
        is_pinned: update.isPinned,
        display_order: update.displayOrder,
        published_at: update.publishedAt,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(`/admin/course-updates/${update.id}`);
    };

    return (
        <>
            <Head title={`ویرایش ${update.title}`} />
            <AdminPageHeader
                title="ویرایش آپدیت"
                description={update.title}
                actions={
                    <AdminButton asChild size="sm" adminVariant="outline">
                        <Link href="/admin/course-updates">بازگشت</Link>
                    </AdminButton>
                }
            />
            <div className={`${surfaceCardClassName} p-4 sm:p-5`}>
                <AdminCourseUpdateForm
                    data={{
                        title: data.title,
                        summary: data.summary,
                        body: data.body,
                        type: data.type,
                        visualTheme: data.visual_theme,
                        status: data.status,
                        isPinned: data.is_pinned,
                        displayOrder: data.display_order,
                        publishedAt: data.published_at,
                    }}
                    errors={errors}
                    processing={processing}
                    formOptions={formOptions}
                    submitLabel="ذخیره تغییرات"
                    onSubmit={submit}
                    onChange={(key, value) => {
                        const fieldMap = {
                            title: 'title',
                            summary: 'summary',
                            body: 'body',
                            type: 'type',
                            visualTheme: 'visual_theme',
                            status: 'status',
                            isPinned: 'is_pinned',
                            displayOrder: 'display_order',
                            publishedAt: 'published_at',
                        } as const;

                        setData(
                            fieldMap[key],
                            value as never,
                        );
                    }}
                />
            </div>
        </>
    );
}
