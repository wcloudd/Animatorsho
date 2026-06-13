import type { FormEvent } from 'react';
import { AdminJalaliDateInput } from '@/components/admin/admin-jalali-date-input';
import { AdminButton } from '@/components/admin/admin-button';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    combineDateTimeLocal,
    splitDateTimeLocal,
} from '@/lib/jalali-date';
import type {
    AdminCourseResourceFormItem,
    AdminCourseResourceFormOptions,
} from '@/types/admin';

type AdminCourseResourceFormProps = {
    data: AdminCourseResourceFormItem;
    errors: Partial<
        Record<
            | keyof AdminCourseResourceFormItem
            | 'type'
            | 'file_path'
            | 'external_url'
            | 'status'
            | 'access_scope'
            | 'course_resource_category_id'
            | 'display_order'
            | 'published_at',
            string
        >
    >;
    processing: boolean;
    formOptions: AdminCourseResourceFormOptions;
    submitLabel: string;
    onSubmit: (event: FormEvent) => void;
    onChange: <K extends keyof AdminCourseResourceFormItem>(
        key: K,
        value: AdminCourseResourceFormItem[K],
    ) => void;
};

export function AdminCourseResourceForm({
    data,
    errors,
    processing,
    formOptions,
    submitLabel,
    onSubmit,
    onChange,
}: AdminCourseResourceFormProps) {
    const { date: publishedDate, time: publishedTime } = splitDateTimeLocal(
        data.publishedAt,
    );
    const isExternalLink = data.type === 'external_link';

    const handlePublishedDateChange = (date: string) => {
        onChange(
            'publishedAt',
            combineDateTimeLocal(date, publishedTime),
        );
    };

    const handlePublishedTimeChange = (time: string) => {
        onChange(
            'publishedAt',
            publishedDate
                ? combineDateTimeLocal(publishedDate, time)
                : null,
        );
    };

    return (
        <form onSubmit={onSubmit} className="flex flex-col gap-5">
            <div className="grid gap-2">
                <Label htmlFor="title">عنوان</Label>
                <Input
                    id="title"
                    value={data.title}
                    onChange={(event) => onChange('title', event.target.value)}
                    required
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">توضیح کوتاه</Label>
                <textarea
                    id="description"
                    value={data.description}
                    onChange={(event) =>
                        onChange('description', event.target.value)
                    }
                    rows={3}
                    className="min-h-[5rem] w-full rounded-xl border border-border/70 bg-surface px-3 py-2 text-sm text-text outline-none ring-purple/30 focus:ring-2"
                />
                <InputError message={errors.description} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="type">نوع منبع</Label>
                    <Select
                        value={data.type}
                        onValueChange={(value) => onChange('type', value)}
                    >
                        <SelectTrigger id="type">
                            <SelectValue placeholder="انتخاب نوع" />
                        </SelectTrigger>
                        <SelectContent>
                            {formOptions.typeOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.type} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="categoryId">دسته‌بندی</Label>
                    <Select
                        value={
                            data.categoryId !== null
                                ? String(data.categoryId)
                                : 'none'
                        }
                        onValueChange={(value) =>
                            onChange(
                                'categoryId',
                                value === 'none' ? null : Number(value),
                            )
                        }
                    >
                        <SelectTrigger id="categoryId">
                            <SelectValue placeholder="بدون دسته" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">بدون دسته</SelectItem>
                            {formOptions.categoryOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError
                        message={errors.course_resource_category_id}
                    />
                </div>
            </div>

            {isExternalLink ? (
                <div className="grid gap-2">
                    <Label htmlFor="externalUrl">آدرس لینک بیرونی</Label>
                    <Input
                        id="externalUrl"
                        value={data.externalUrl}
                        onChange={(event) =>
                            onChange('externalUrl', event.target.value)
                        }
                        placeholder="https://example.com/reference"
                        dir="ltr"
                        className="text-left"
                    />
                    <InputError message={errors.external_url} />
                </div>
            ) : (
                <div className="grid gap-2">
                    <Label htmlFor="filePath">مسیر فایل عمومی</Label>
                    <Input
                        id="filePath"
                        value={data.filePath}
                        onChange={(event) =>
                            onChange('filePath', event.target.value)
                        }
                        placeholder="/media/student-panel/resources/example.pdf"
                        dir="ltr"
                        className="text-left"
                    />
                    <p className="text-xs text-muted">
                        مسیر فایل در پوشه public یا آدرس قابل دسترس برای
                        هنرجو. آپلود فایل در این نسخه پشتیبانی نمی‌شود.
                    </p>
                    <InputError message={errors.file_path} />
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="status">وضعیت انتشار</Label>
                    <Select
                        value={data.status}
                        onValueChange={(value) => onChange('status', value)}
                    >
                        <SelectTrigger id="status">
                            <SelectValue placeholder="انتخاب وضعیت" />
                        </SelectTrigger>
                        <SelectContent>
                            {formOptions.statusOptions.map((option) => (
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
                    <Label htmlFor="accessScope">محدوده دسترسی</Label>
                    <Select
                        value={data.accessScope}
                        onValueChange={(value) =>
                            onChange('accessScope', value)
                        }
                    >
                        <SelectTrigger id="accessScope">
                            <SelectValue placeholder="انتخاب محدوده" />
                        </SelectTrigger>
                        <SelectContent>
                            {formOptions.accessScopeOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <p className="text-xs text-muted">
                        در این نسخه فقط منابع «همه هنرجوها» در پنل نمایش داده
                        می‌شوند.
                    </p>
                    <InputError message={errors.access_scope} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <AdminJalaliDateInput
                        id="publishedAt"
                        label="تاریخ انتشار"
                        value={publishedDate}
                        onChange={handlePublishedDateChange}
                        placeholder="انتخاب تاریخ شمسی"
                    />
                    <div className="grid gap-2">
                        <Label htmlFor="publishedTime">ساعت انتشار</Label>
                        <Input
                            id="publishedTime"
                            type="time"
                            value={publishedTime}
                            onChange={(event) =>
                                handlePublishedTimeChange(event.target.value)
                            }
                            disabled={publishedDate === ''}
                        />
                    </div>
                    <p className="text-xs text-muted">
                        برای انتشار، اگر تاریخ خالی بماند زمان فعلی ثبت
                        می‌شود.
                    </p>
                    <InputError message={errors.published_at} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="displayOrder">ترتیب نمایش</Label>
                    <Input
                        id="displayOrder"
                        type="number"
                        min={0}
                        value={data.displayOrder}
                        onChange={(event) =>
                            onChange('displayOrder', Number(event.target.value))
                        }
                        required
                    />
                    <InputError message={errors.display_order} />
                </div>
            </div>

            <AdminButton type="submit" disabled={processing}>
                {submitLabel}
            </AdminButton>
        </form>
    );
}
