import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCallout } from '@/components/admin/admin-callout';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import InputError from '@/components/input-error';
import { surfaceCardClassName } from '@/components/page-container';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type {
    AdminCardToCardDisplay,
    AdminIntegrationStatus,
    AdminSiteSettings,
} from '@/types/admin';

type PageProps = {
    settings: AdminSiteSettings;
    integrations: AdminIntegrationStatus;
    cardToCard: AdminCardToCardDisplay;
};

function IntegrationStatusBadge({ configured }: { configured: boolean }) {
    return (
        <span
            className={cn(
                'rounded-pill px-3 py-1 text-xs font-medium ring-1',
                configured
                    ? 'bg-green-soft text-green ring-green/15'
                    : 'bg-red/10 text-red ring-red/15',
            )}
        >
            {configured ? 'پیکربندی شده' : 'پیکربندی نشده'}
        </span>
    );
}

function ReadOnlyValue({
    value,
    dir,
    className,
}: {
    value: string;
    dir?: 'ltr' | 'rtl';
    className?: string;
}) {
    return (
        <p
            dir={dir}
            className={cn(
                'rounded-md border border-purple/10 bg-purple-soft/30 px-3 py-2 text-sm font-medium text-text',
                className,
            )}
        >
            {value}
        </p>
    );
}

export default function AdminSiteSettingsIndex({
    settings,
    integrations,
    cardToCard,
}: PageProps) {
    const { data, setData, patch, processing, errors } = useForm({
        purchases_enabled: settings.purchasesEnabled,
        maintenance_mode_enabled: settings.maintenanceModeEnabled,
        maintenance_title: settings.maintenanceTitle,
        maintenance_message: settings.maintenanceMessage,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch('/admin/site-settings');
    };

    return (
        <>
            <Head title="تنظیمات سایت" />
            <AdminPageHeader
                title="تنظیمات سایت"
                description="کنترل‌های عملیاتی سایت: بروزرسانی، خرید، کارت‌به‌کارت و وضعیت اتصال‌ها."
            />

            <form
                onSubmit={submit}
                className="flex flex-col gap-5"
            >
                <section
                    className={cn(surfaceCardClassName, 'flex flex-col gap-4 p-4 sm:p-5')}
                >
                    <AdminSectionTitle>وضعیت سایت</AdminSectionTitle>

                    <label className="flex items-center gap-2 text-sm text-text">
                        <Checkbox
                            checked={data.maintenance_mode_enabled}
                            onCheckedChange={(checked) =>
                                setData(
                                    'maintenance_mode_enabled',
                                    checked === true,
                                )
                            }
                        />
                        حالت بروزرسانی فعال باشد
                    </label>
                    <InputError message={errors.maintenance_mode_enabled} />

                    {data.maintenance_mode_enabled ? (
                        <AdminCallout variant="warning">
                            کاربران عادی صفحه بروزرسانی می‌بینند. ورود ادمین و
                            پنل مدیریت همچنان در دسترس است.
                        </AdminCallout>
                    ) : null}

                    <div className="grid gap-2">
                        <Label htmlFor="maintenance_title">عنوان بروزرسانی</Label>
                        <Input
                            id="maintenance_title"
                            value={data.maintenance_title}
                            onChange={(event) =>
                                setData('maintenance_title', event.target.value)
                            }
                            placeholder="در حال بروزرسانی هستیم"
                        />
                        <InputError message={errors.maintenance_title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="maintenance_message">پیام بروزرسانی</Label>
                        <textarea
                            id="maintenance_message"
                            value={data.maintenance_message}
                            onChange={(event) =>
                                setData('maintenance_message', event.target.value)
                            }
                            rows={4}
                            className="border-input bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                            placeholder="در حال به‌روزرسانی سایت هستیم..."
                        />
                        <InputError message={errors.maintenance_message} />
                    </div>
                </section>

                <section
                    className={cn(surfaceCardClassName, 'flex flex-col gap-4 p-4 sm:p-5')}
                >
                    <AdminSectionTitle>وضعیت خرید</AdminSectionTitle>

                    <label className="flex items-center gap-2 text-sm text-text">
                        <Checkbox
                            checked={data.purchases_enabled}
                            onCheckedChange={(checked) =>
                                setData('purchases_enabled', checked === true)
                            }
                        />
                        ثبت‌نام و خرید دوره فعال باشد
                    </label>
                    <InputError message={errors.purchases_enabled} />

                    {!data.purchases_enabled ? (
                        <AdminCallout variant="warning">
                            کاربران نمی‌توانند سفارش جدید ثبت کنند. پرداخت‌های
                            در جریان و بازیابی سفارش‌های قبلی متأثر نمی‌شوند.
                        </AdminCallout>
                    ) : null}
                </section>

                <section
                    className={cn(surfaceCardClassName, 'flex flex-col gap-4 p-4 sm:p-5')}
                >
                    <AdminSectionTitle>وضعیت اتصال‌ها</AdminSectionTitle>

                    <div className="flex flex-col gap-3 text-sm text-text">
                        <div className="flex items-center justify-between gap-3">
                            <span>زرین‌پال</span>
                            <IntegrationStatusBadge
                                configured={integrations.zarinpalConfigured}
                            />
                        </div>
                        <div className="flex items-center justify-between gap-3">
                            <span>فراز اس‌ام‌اس</span>
                            <IntegrationStatusBadge
                                configured={integrations.farazSmsConfigured}
                            />
                        </div>
                        <div className="flex items-center justify-between gap-3">
                            <span>SpotPlayer</span>
                            <IntegrationStatusBadge
                                configured={integrations.spotPlayerConfigured}
                            />
                        </div>
                    </div>

                    <div className="border-t border-purple/10 pt-4">
                        <div className="flex flex-col gap-4">
                            <div className="flex items-center justify-between gap-3 text-sm text-text">
                                <span className="font-medium">کارت‌به‌کارت</span>
                                <IntegrationStatusBadge
                                    configured={cardToCard.configured}
                                />
                            </div>

                            <div className="flex items-center justify-between gap-3 text-sm text-text">
                                <span className="text-muted">منبع</span>
                                <span className="font-medium">{cardToCard.source}</span>
                            </div>

                            <div className="grid gap-2">
                                <Label>شماره کارت</Label>
                                <ReadOnlyValue
                                    value={cardToCard.cardNumber}
                                    dir="ltr"
                                    className="text-left"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label>نام صاحب کارت</Label>
                                <ReadOnlyValue value={cardToCard.cardOwnerName} />
                            </div>

                            <p className="text-xs leading-relaxed text-muted">
                                برای تغییر اطلاعات کارت‌به‌کارت، مقدارهای
                                CARD_TO_CARD_NUMBER و CARD_TO_CARD_OWNER_NAME را
                                در فایل .env ویرایش کنید.
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    className={cn(surfaceCardClassName, 'flex flex-col gap-4 p-4 sm:p-5')}
                >
                    <AdminSectionTitle>نکات امنیتی اتصال‌ها</AdminSectionTitle>

                    <AdminCallout variant="warning">
                        کلیدهای API و رمزهای اتصال (زرین‌پال، فراز اس‌ام‌اس،
                        SpotPlayer) فقط در env سرور نگهداری می‌شوند و در این
                        صفحه نمایش داده نمی‌شوند.
                    </AdminCallout>

                    <p className="text-xs leading-relaxed text-muted">
                        {/* TODO: Encrypted credential management can be implemented later as a separate high-security slice. */}
                        مدیریت رمزنگاری‌شده اعتبارنامه‌ها در اسلایس امنیتی
                        جداگانه‌ای پیاده‌سازی خواهد شد.
                    </p>

                    <p className="text-xs leading-relaxed text-muted">
                        شماره موبایل ادمین برای اعلان‌های پیامکی در{' '}
                        <Link
                            href="/admin/sms"
                            className="font-medium text-purple underline-offset-2 hover:underline"
                        >
                            تنظیمات پیامک
                        </Link>{' '}
                        مدیریت می‌شود.
                    </p>
                </section>

                <AdminButton type="submit" adminVariant="brand" disabled={processing}>
                    ذخیره تنظیمات
                </AdminButton>
            </form>
        </>
    );
}
