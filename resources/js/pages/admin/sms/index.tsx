import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import type { FormEvent } from 'react';
import { AdminCallout } from '@/components/admin/admin-callout';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import InputError from '@/components/input-error';
import { surfaceCardClassName } from '@/components/page-container';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { AdminSmsSettings, AdminSmsTemplate } from '@/types/admin';

type PageProps = {
    settings: AdminSmsSettings;
    templates: AdminSmsTemplate[];
};

function SmsSettingsForm({ settings }: { settings: AdminSmsSettings }) {
    const { data, setData, patch, processing, errors } = useForm({
        enabled: settings.enabled,
        admin_notifications_enabled: settings.adminNotificationsEnabled,
        admin_mobile: settings.adminMobile ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch('/admin/sms/settings');
    };

    return (
        <form
            onSubmit={submit}
            className={cn(surfaceCardClassName, 'flex flex-col gap-5 p-4 sm:p-5')}
        >
            <div className="flex items-center justify-between gap-3 border-b border-purple/8 pb-3">
                <h2 className="font-liana text-base text-purple">
                    تنظیمات پیامک
                </h2>
                <div className="flex flex-col items-end gap-1">
                    <span className="rounded-pill bg-purple-soft px-3 py-1 text-xs font-medium text-purple ring-1 ring-purple/10">
                        درایور فعلی: {settings.driverLabel}
                    </span>
                    {!settings.driverConfigured && (
                        <span className="text-xs text-red/75">
                            env ناقص
                        </span>
                    )}
                </div>
            </div>

            {!settings.driverConfigured ? (
                <AdminCallout variant="warning">
                    تنظیمات env ناقص است. ارسال پیامک ممکن است با خطا مواجه
                    شود.
                </AdminCallout>
            ) : null}

            <label className="flex items-center gap-2 text-sm text-text">
                <Checkbox
                    checked={data.enabled}
                    onCheckedChange={(checked) =>
                        setData('enabled', checked === true)
                    }
                />
                ارسال پیامک فعال باشد
            </label>
            <InputError message={errors.enabled} />

            <label className="flex items-center gap-2 text-sm text-text">
                <Checkbox
                    checked={data.admin_notifications_enabled}
                    onCheckedChange={(checked) =>
                        setData(
                            'admin_notifications_enabled',
                            checked === true,
                        )
                    }
                />
                اعلان‌های ادمین فعال باشد
            </label>
            <InputError message={errors.admin_notifications_enabled} />

            <div className="grid gap-2">
                <Label htmlFor="admin_mobile">شماره موبایل ادمین</Label>
                <Input
                    id="admin_mobile"
                    value={data.admin_mobile}
                    onChange={(event) =>
                        setData('admin_mobile', event.target.value)
                    }
                    placeholder="09123456789"
                    dir="ltr"
                    className="text-left"
                />
                <InputError message={errors.admin_mobile} />
            </div>

            <AdminButton type="submit" adminVariant="brand" disabled={processing}>
                ذخیره تنظیمات
            </AdminButton>
        </form>
    );
}

function SmsTemplateForm({ template }: { template: AdminSmsTemplate }) {
    const { data, setData, patch, processing, errors } = useForm({
        title: template.title,
        body: template.body,
        is_enabled: template.isEnabled,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(`/admin/sms/templates/${template.id}`);
    };

    return (
        <form
            onSubmit={submit}
            className={cn(surfaceCardClassName, 'flex flex-col gap-4 p-4 sm:p-5')}
        >
            <div className="flex flex-wrap items-start justify-between gap-2 border-b border-purple/8 pb-3">
                <div>
                    <h3 className="text-sm font-bold text-text">
                        {template.title}
                    </h3>
                    <p className="mt-1 font-mono text-xs text-muted">
                        {template.key}
                    </p>
                </div>
                <label className="flex items-center gap-2 text-xs text-text">
                    <Checkbox
                        checked={data.is_enabled}
                        onCheckedChange={(checked) =>
                            setData('is_enabled', checked === true)
                        }
                    />
                    فعال
                </label>
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`title-${template.id}`}>عنوان</Label>
                <Input
                    id={`title-${template.id}`}
                    value={data.title}
                    onChange={(event) => setData('title', event.target.value)}
                    required
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`body-${template.id}`}>متن پیام</Label>
                <textarea
                    id={`body-${template.id}`}
                    value={data.body}
                    onChange={(event) => setData('body', event.target.value)}
                    required
                    rows={3}
                    className="min-h-24 w-full rounded-xl border border-border bg-surface px-3 py-2 text-sm text-text outline-none ring-purple/20 focus:ring-2"
                />
                <InputError message={errors.body} />
                {template.description ? (
                    <p className="text-xs text-muted">
                        متغیرها: {template.description}
                    </p>
                ) : null}
            </div>

            <AdminButton type="submit" size="sm" adminVariant="brand" disabled={processing}>
                ذخیره قالب
            </AdminButton>
        </form>
    );
}

export default function AdminSmsIndex({ settings, templates }: PageProps) {
    return (
        <>
            <Head title="مدیریت پیامک" />
            <AdminPageHeader
                title="پیامک"
                description="تنظیمات و قالب‌های پیامک"
            />

            <div className="flex flex-col gap-6">
                <SmsSettingsForm settings={settings} />

                <Link
                    href="/admin/sms/logs"
                    className={cn(
                        surfaceCardClassName,
                        'group flex items-center justify-between gap-3 p-4 transition hover:ring-purple/25 sm:p-5',
                    )}
                >
                    <div>
                        <p className="text-sm font-bold text-text">
                            مشاهده گزارش پیامک‌ها
                        </p>
                        <p className="mt-1 text-xs text-muted">
                            تاریخچه ارسال، وضعیت و جزئیات فنی
                        </p>
                    </div>
                    <ChevronLeft
                        className="size-5 shrink-0 text-purple transition group-hover:-translate-x-0.5"
                        aria-hidden
                    />
                </Link>

                <section className="flex flex-col gap-3">
                    <AdminSectionTitle className="mb-0">
                        قالب پیام‌ها
                    </AdminSectionTitle>
                    {templates.map((template) => (
                        <SmsTemplateForm key={template.id} template={template} />
                    ))}
                </section>
            </div>
        </>
    );
}
