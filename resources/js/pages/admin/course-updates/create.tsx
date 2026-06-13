import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminCourseUpdateForm } from '@/components/admin/admin-course-update-form';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { surfaceCardClassName } from '@/components/page-container';
import type { AdminCourseUpdateFormOptions } from '@/types/admin';

type PageProps = {
    formOptions: AdminCourseUpdateFormOptions;
};

const defaultFormValues = {
    title: '',
    summary: '',
    body: '',
    type: 'announcement',
    visual_theme: 'default',
    status: 'draft',
    is_pinned: false,
    display_order: 0,
    published_at: null as string | null,
};

export default function AdminCourseUpdatesCreate({
    formOptions,
}: PageProps) {
    const { data, setData, post, processing, errors } = useForm(defaultFormValues);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/admin/course-updates');
    };

    return (
        <>
            <Head title="آپدیت جدید دوره" />
            <AdminPageHeader
                title="آپدیت جدید"
                description="اعلان جدید برای پنل هنرجو ایجاد کنید."
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
                    submitLabel="ایجاد آپدیت"
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
