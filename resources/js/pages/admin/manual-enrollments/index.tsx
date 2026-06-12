import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminButton } from '@/components/admin/admin-button';
import { AdminCallout } from '@/components/admin/admin-callout';
import { AdminDetailRow } from '@/components/admin/admin-detail-row';
import { AdminEmptyState } from '@/components/admin/admin-empty-state';
import { AdminInfoGrid } from '@/components/admin/admin-info-grid';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminSectionTitle } from '@/components/admin/admin-section-title';
import InputError from '@/components/input-error';
import { surfaceCardClassName } from '@/components/page-container';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatAdminDate } from '@/lib/format-admin-date';
import { cn } from '@/lib/utils';
import type { AdminStatusOption } from '@/types/admin';

type PackageOption = {
    id: number;
    title: string;
    slug: string;
    priceFormatted: string;
};

type RecentGrant = {
    id: number;
    orderNumber: string;
    customerName: string | null;
    customerMobile: string | null;
    packageTitle: string;
    sourceLabel: string | null;
    licenseStatus: string | null;
    createdAt: string | null;
};

type PageProps = {
    packages: PackageOption[];
    sourceOptions: AdminStatusOption[];
    recentGrants: RecentGrant[];
};

const textareaClassName = cn(
    'border-input bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm text-start shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
    'min-h-[80px]',
);

export default function AdminManualEnrollmentsIndex({
    packages,
    sourceOptions,
    recentGrants,
}: PageProps) {
    const defaultPackageId =
        packages.length > 0 ? String(packages[0].id) : '';

    const { data, setData, post, processing, errors, reset } = useForm({
        customer_name: '',
        user_lookup: '',
        customer_mobile: '',
        course_package_id: defaultPackageId,
        source: 'eitaa',
        admin_note: '',
        license_key: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/admin/manual-enrollments', {
            preserveScroll: true,
            onSuccess: () => {
                reset(
                    'customer_name',
                    'user_lookup',
                    'customer_mobile',
                    'admin_note',
                    'license_key',
                );
            },
        });
    };

    return (
        <>
            <Head title="ثبت دستی دسترسی" />
            <AdminPageHeader
                title="ثبت دستی دسترسی"
                description="اعطای دسترسی به خریداران خارج از سایت (مثل ایتا) با ثبت سفارش، پرداخت و لایسنس."
            />

            <div className="flex flex-col gap-5">
                <AdminCallout tone="info">
                    اگر کاربر قبلاً در سایت ثبت‌نام کرده، شماره موبایل یا نام
                    کاربری او را در «جستجوی کاربر موجود» وارد کنید. اگر کاربر
                    پیدا نشود، فقط با شماره موبایل معتبر کاربر جدید ساخته
                    می‌شود.
                    <br />
                    <br />
                    اگر کلید لایسنس SpotPlayer وارد شود یا API با موفقیت فعال
                    کند، کاربر دسترسی فعال می‌گیرد و می‌تواند «داشبورد هنرجو»
                    را در مسیر /course باز کند. در غیر این صورت دسترسی در حالت
                    انتظار می‌ماند و باید از صفحه{' '}
                    <a
                        href="/admin/licenses"
                        className="font-bold text-purple underline-offset-2 hover:underline"
                    >
                        لایسنس‌ها
                    </a>{' '}
                    فعال شود. اگر حساب کاربری تازه ساخته شده، کاربر با ورود OTP
                    (شماره موبایل) وارد می‌شود.
                </AdminCallout>

                <form
                    onSubmit={submit}
                    className={cn(surfaceCardClassName, 'flex flex-col gap-4 p-4 sm:p-5')}
                >
                    <AdminSectionTitle>فرم ثبت دستی</AdminSectionTitle>

                    <div className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="customer_name">نام مشتری</Label>
                            <Input
                                id="customer_name"
                                value={data.customer_name}
                                onChange={(event) =>
                                    setData('customer_name', event.target.value)
                                }
                                autoComplete="name"
                            />
                            <InputError message={errors.customer_name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="user_lookup">
                                جستجوی کاربر موجود با موبایل یا نام کاربری
                            </Label>
                            <Input
                                id="user_lookup"
                                value={data.user_lookup}
                                onChange={(event) =>
                                    setData('user_lookup', event.target.value)
                                }
                                dir="ltr"
                                placeholder="09123456789 یا username"
                            />
                            <p className="text-xs text-muted">
                                اگر کاربر قبلاً در سایت ثبت‌نام کرده، شماره
                                موبایل یا نام کاربری او را وارد کنید.
                            </p>
                            <InputError message={errors.user_lookup} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="customer_mobile">
                                شماره موبایل (برای کاربر جدید یا ثبت در سفارش)
                            </Label>
                            <Input
                                id="customer_mobile"
                                value={data.customer_mobile}
                                onChange={(event) =>
                                    setData(
                                        'customer_mobile',
                                        event.target.value,
                                    )
                                }
                                dir="ltr"
                                inputMode="tel"
                                autoComplete="tel"
                                placeholder="09123456789"
                            />
                            <p className="text-xs text-muted">
                                اگر کاربر پیدا نشود، فقط با شماره موبایل معتبر
                                کاربر جدید ساخته می‌شود.
                            </p>
                            <InputError message={errors.customer_mobile} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="course_package_id">بسته دوره</Label>
                            <Select
                                value={data.course_package_id}
                                onValueChange={(value) =>
                                    setData('course_package_id', value)
                                }
                            >
                                <SelectTrigger id="course_package_id">
                                    <SelectValue placeholder="انتخاب بسته" />
                                </SelectTrigger>
                                <SelectContent>
                                    {packages.map((pkg) => (
                                        <SelectItem
                                            key={pkg.id}
                                            value={String(pkg.id)}
                                        >
                                            {pkg.title} — {pkg.priceFormatted}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.course_package_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="source">منبع خرید</Label>
                            <Select
                                value={data.source}
                                onValueChange={(value) =>
                                    setData('source', value)
                                }
                            >
                                <SelectTrigger id="source">
                                    <SelectValue placeholder="انتخاب منبع" />
                                </SelectTrigger>
                                <SelectContent>
                                    {sourceOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.source} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="admin_note">یادداشت ادمین (اختیاری)</Label>
                            <textarea
                                id="admin_note"
                                className={textareaClassName}
                                value={data.admin_note}
                                onChange={(event) =>
                                    setData('admin_note', event.target.value)
                                }
                            />
                            <InputError message={errors.admin_note} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="license_key">
                                کلید لایسنس SpotPlayer (اختیاری)
                            </Label>
                            <Input
                                id="license_key"
                                value={data.license_key}
                                onChange={(event) =>
                                    setData('license_key', event.target.value)
                                }
                                dir="ltr"
                                placeholder="در صورت صدور قبلی کلید را وارد کنید"
                            />
                            <InputError message={errors.license_key} />
                        </div>
                    </div>

                    <AdminButton type="submit" disabled={processing}>
                        ثبت دسترسی
                    </AdminButton>
                </form>

                <section
                    className={cn(surfaceCardClassName, 'flex flex-col gap-4 p-4 sm:p-5')}
                >
                    <AdminSectionTitle>ثبت‌های اخیر (خارج از سایت)</AdminSectionTitle>

                    {recentGrants.length === 0 ? (
                        <AdminEmptyState message="هنوز ثبت دستی انجام نشده است." />
                    ) : (
                        <div className="flex flex-col gap-3">
                            {recentGrants.map((grant) => (
                                <div
                                    key={grant.id}
                                    className="rounded-2xl border border-[#e8e0f0] bg-bg p-4"
                                >
                                    <p className="text-sm font-bold text-text">
                                        {grant.orderNumber}
                                    </p>
                                    <p className="mt-1 text-xs text-muted">
                                        {grant.packageTitle}
                                    </p>
                                    <AdminInfoGrid className="mt-3">
                                        <AdminDetailRow
                                            label="نام"
                                            value={grant.customerName}
                                            truncateValue
                                        />
                                        <AdminDetailRow
                                            label="موبایل"
                                            value={grant.customerMobile}
                                        />
                                        <AdminDetailRow
                                            label="منبع"
                                            value={grant.sourceLabel}
                                        />
                                        <AdminDetailRow
                                            label="لایسنس"
                                            value={grant.licenseStatus}
                                        />
                                        <AdminDetailRow
                                            label="تاریخ"
                                            value={formatAdminDate(
                                                grant.createdAt,
                                            )}
                                        />
                                    </AdminInfoGrid>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
