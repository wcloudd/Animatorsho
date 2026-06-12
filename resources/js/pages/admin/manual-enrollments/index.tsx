import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
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

type LookupPreviewStatus =
    | 'empty'
    | 'found'
    | 'not_found'
    | 'needs_mobile'
    | 'invalid';

type LookupPreviewUser = {
    id: number;
    name: string;
    username: string | null;
    mobile: string | null;
    hasMobile: boolean;
};

type LookupPreview = {
    status: LookupPreviewStatus;
    message: string | null;
    user: LookupPreviewUser | null;
};

const lookupPreviewStyles: Record<
    LookupPreviewStatus,
    { box: string; title: string }
> = {
    empty: {
        box: 'rounded-xl bg-purple-soft/50 px-3 py-2 ring-1 ring-purple/10',
        title: 'text-xs font-medium text-muted',
    },
    found: {
        box: 'rounded-xl bg-green-soft px-3 py-2 ring-1 ring-green/15',
        title: 'text-xs font-medium text-green',
    },
    not_found: {
        box: 'rounded-xl bg-gold-soft px-3 py-2 ring-1 ring-gold/15',
        title: 'text-xs font-medium text-gold',
    },
    needs_mobile: {
        box: 'rounded-xl bg-gold-soft px-3 py-2 ring-1 ring-gold/15',
        title: 'text-xs font-medium text-gold',
    },
    invalid: {
        box: 'rounded-xl bg-red/10 px-3 py-2 ring-1 ring-red/15',
        title: 'text-xs font-medium text-red/80',
    },
};

function LookupPreviewCard({
    preview,
    stale,
}: {
    preview: LookupPreview;
    stale: boolean;
}) {
    if (preview.status === 'empty' && !stale) {
        return null;
    }

    const styles = lookupPreviewStyles[preview.status];

    return (
        <div className={cn(styles.box, 'flex flex-col gap-2')}>
            {stale ? (
                <p className="text-xs font-medium text-muted">
                    نتیجه بررسی قدیمی است؛ دوباره «بررسی کاربر» را بزنید.
                </p>
            ) : null}
            {preview.message ? (
                <p className={styles.title}>{preview.message}</p>
            ) : null}
            {preview.user ? (
                <AdminInfoGrid>
                    <AdminDetailRow
                        label="نام"
                        value={preview.user.name}
                        truncateValue
                    />
                    <AdminDetailRow
                        label="نام کاربری"
                        value={preview.user.username ?? '—'}
                    />
                    <AdminDetailRow
                        label="موبایل"
                        value={preview.user.mobile ?? '—'}
                    />
                </AdminInfoGrid>
            ) : null}
        </div>
    );
}

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

    const [lookupPreview, setLookupPreview] = useState<LookupPreview | null>(
        null,
    );
    const [lookupCheckedFor, setLookupCheckedFor] = useState('');
    const [lookupChecking, setLookupChecking] = useState(false);
    const [lookupError, setLookupError] = useState<string | null>(null);

    const lookupIsStale =
        lookupPreview !== null &&
        lookupCheckedFor !== data.user_lookup.trim();

    const checkUser = async () => {
        const lookup = data.user_lookup.trim();

        setLookupChecking(true);
        setLookupError(null);

        try {
            const params = new URLSearchParams();

            if (lookup !== '') {
                params.set('user_lookup', lookup);
            }

            if (data.customer_mobile.trim() !== '') {
                params.set('customer_mobile', data.customer_mobile.trim());
            }

            const response = await fetch(
                `/admin/manual-enrollments/lookup?${params.toString()}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            if (!response.ok) {
                throw new Error('lookup_failed');
            }

            const preview = (await response.json()) as LookupPreview;

            setLookupPreview(preview);
            setLookupCheckedFor(lookup);
        } catch {
            setLookupError('بررسی کاربر انجام نشد. دوباره تلاش کنید.');
            setLookupPreview(null);
            setLookupCheckedFor('');
        } finally {
            setLookupChecking(false);
        }
    };

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
                setLookupPreview(null);
                setLookupCheckedFor('');
                setLookupError(null);
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
                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Input
                                    id="user_lookup"
                                    value={data.user_lookup}
                                    onChange={(event) => {
                                        setData(
                                            'user_lookup',
                                            event.target.value,
                                        );
                                        setLookupPreview(null);
                                        setLookupCheckedFor('');
                                        setLookupError(null);
                                    }}
                                    dir="ltr"
                                    placeholder="09123456789 یا username"
                                    className="sm:flex-1"
                                />
                                <AdminButton
                                    type="button"
                                    adminVariant="outline"
                                    disabled={
                                        lookupChecking ||
                                        data.user_lookup.trim() === ''
                                    }
                                    onClick={() => void checkUser()}
                                    className="shrink-0"
                                >
                                    {lookupChecking
                                        ? 'در حال بررسی...'
                                        : 'بررسی کاربر'}
                                </AdminButton>
                            </div>
                            <p className="text-xs text-muted">
                                اگر کاربر قبلاً در سایت ثبت‌نام کرده، شماره
                                موبایل یا نام کاربری او را وارد کنید.
                            </p>
                            {lookupError ? (
                                <p className="text-xs text-red">{lookupError}</p>
                            ) : null}
                            {lookupPreview ? (
                                <LookupPreviewCard
                                    preview={lookupPreview}
                                    stale={lookupIsStale}
                                />
                            ) : null}
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
