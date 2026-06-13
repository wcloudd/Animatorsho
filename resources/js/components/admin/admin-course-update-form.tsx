import type { FormEvent } from 'react';
import { AdminJalaliDateInput } from '@/components/admin/admin-jalali-date-input';
import { AdminButton } from '@/components/admin/admin-button';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
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
    AdminCourseUpdateFormItem,
    AdminCourseUpdateFormOptions,
} from '@/types/admin';

type AdminCourseUpdateFormProps = {
    data: AdminCourseUpdateFormItem;
    errors: Partial<Record<keyof AdminCourseUpdateFormItem | 'type' | 'visual_theme' | 'status' | 'is_pinned' | 'display_order' | 'published_at', string>>;
    processing: boolean;
    formOptions: AdminCourseUpdateFormOptions;
    submitLabel: string;
    onSubmit: (event: FormEvent) => void;
    onChange: <K extends keyof AdminCourseUpdateFormItem>(
        key: K,
        value: AdminCourseUpdateFormItem[K],
    ) => void;
};

export function AdminCourseUpdateForm({
    data,
    errors,
    processing,
    formOptions,
    submitLabel,
    onSubmit,
    onChange,
}: AdminCourseUpdateFormProps) {
    const { date: publishedDate, time: publishedTime } = splitDateTimeLocal(
        data.publishedAt,
    );

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
                <Label htmlFor="summary">خلاصه</Label>
                <textarea
                    id="summary"
                    value={data.summary}
                    onChange={(event) => onChange('summary', event.target.value)}
                    rows={3}
                    className="min-h-[5rem] w-full rounded-xl border border-border/70 bg-surface px-3 py-2 text-sm text-text outline-none ring-purple/30 focus:ring-2"
                />
                <InputError message={errors.summary} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="body">متن کامل (اختیاری)</Label>
                <textarea
                    id="body"
                    value={data.body}
                    onChange={(event) => onChange('body', event.target.value)}
                    rows={5}
                    className="min-h-[8rem] w-full rounded-xl border border-border/70 bg-surface px-3 py-2 text-sm text-text outline-none ring-purple/30 focus:ring-2"
                />
                <InputError message={errors.body} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="type">نوع آپدیت</Label>
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
                    <Label htmlFor="visualTheme">تم بصری</Label>
                    <Select
                        value={data.visualTheme}
                        onValueChange={(value) =>
                            onChange('visualTheme', value)
                        }
                    >
                        <SelectTrigger id="visualTheme">
                            <SelectValue placeholder="انتخاب تم" />
                        </SelectTrigger>
                        <SelectContent>
                            {formOptions.visualThemeOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.visual_theme} />
                </div>
            </div>

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

            <div className="flex items-center gap-2">
                <Checkbox
                    id="isPinned"
                    checked={data.isPinned}
                    onCheckedChange={(checked) =>
                        onChange('isPinned', checked === true)
                    }
                />
                <Label htmlFor="isPinned">سنجاق در بالای لیست آپدیت‌ها</Label>
            </div>
            <InputError message={errors.is_pinned} />

            <AdminButton type="submit" disabled={processing}>
                {submitLabel}
            </AdminButton>
        </form>
    );
}
