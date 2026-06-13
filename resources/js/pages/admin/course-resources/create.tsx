import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminCourseResourceForm } from '@/components/admin/admin-course-resource-form';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { surfaceCardClassName } from '@/components/page-container';
import type { AdminCourseResourceFormOptions } from '@/types/admin';

type PageProps = {
    formOptions: AdminCourseResourceFormOptions;
};

const defaultFormValues = {
    title: '',
    description: '',
    type: 'pdf',
    file_path: '',
    external_url: '',
    status: 'draft',
    access_scope: 'all_students',
    course_package_id: null as number | null,
    course_resource_category_id: null as number | null,
    display_order: 0,
    published_at: null as string | null,
};

export default function AdminCourseResourcesCreate({
    formOptions,
}: PageProps) {
    const { data, setData, post, processing, errors } = useForm(defaultFormValues);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/admin/course-resources');
    };

    return (
        <>
            <Head title="منبع جدید تمرین" />
            <AdminPageHeader
                title="منبع جدید"
                description="فایل یا لینک جدید برای کتابخانه تمرین ایجاد کنید."
                actions={
                    <AdminButton asChild size="sm" adminVariant="outline">
                        <Link href="/admin/course-resources">بازگشت</Link>
                    </AdminButton>
                }
            />
            <div className={`${surfaceCardClassName} p-4 sm:p-5`}>
                <AdminCourseResourceForm
                    data={{
                        title: data.title,
                        description: data.description,
                        type: data.type,
                        filePath: data.file_path,
                        externalUrl: data.external_url,
                        status: data.status,
                        accessScope: data.access_scope,
                        coursePackageId: data.course_package_id,
                        categoryId: data.course_resource_category_id,
                        displayOrder: data.display_order,
                        publishedAt: data.published_at,
                    }}
                    errors={errors}
                    processing={processing}
                    formOptions={formOptions}
                    submitLabel="ایجاد منبع"
                    onSubmit={submit}
                    onChange={(key, value) => {
                        const fieldMap = {
                            title: 'title',
                            description: 'description',
                            type: 'type',
                            filePath: 'file_path',
                            externalUrl: 'external_url',
                            status: 'status',
                            accessScope: 'access_scope',
                            coursePackageId: 'course_package_id',
                            categoryId: 'course_resource_category_id',
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
