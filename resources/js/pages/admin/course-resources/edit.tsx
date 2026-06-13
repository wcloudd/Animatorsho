import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminCourseResourceForm } from '@/components/admin/admin-course-resource-form';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { surfaceCardClassName } from '@/components/page-container';
import type {
    AdminCourseResourceFormItem,
    AdminCourseResourceFormOptions,
} from '@/types/admin';

type PageProps = {
    resource: AdminCourseResourceFormItem & { id: number };
    formOptions: AdminCourseResourceFormOptions;
};

export default function AdminCourseResourcesEdit({
    resource,
    formOptions,
}: PageProps) {
    const { data, setData, patch, processing, errors } = useForm({
        title: resource.title,
        description: resource.description,
        type: resource.type,
        file_path: resource.filePath,
        external_url: resource.externalUrl,
        status: resource.status,
        access_scope: resource.accessScope,
        course_package_id: resource.coursePackageId,
        course_resource_category_id: resource.categoryId,
        display_order: resource.displayOrder,
        published_at: resource.publishedAt,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(`/admin/course-resources/${resource.id}`);
    };

    return (
        <>
            <Head title={`ویرایش ${resource.title}`} />
            <AdminPageHeader
                title="ویرایش منبع"
                description={resource.title}
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
                    submitLabel="ذخیره تغییرات"
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
