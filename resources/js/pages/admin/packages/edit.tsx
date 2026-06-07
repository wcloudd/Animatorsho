import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import InputError from '@/components/input-error';
import { surfaceCardClassName } from '@/components/page-container';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AdminPackageEdit } from '@/types/admin';

type PageProps = {
    package: AdminPackageEdit;
};

export default function AdminPackagesEdit({ package: pkg }: PageProps) {
    const { data, setData, patch, processing, errors } = useForm({
        title: pkg.title,
        price_toman: pkg.priceToman,
        is_active: pkg.isActive,
        display_order: pkg.displayOrder,
        spotplayer_course_ids_input: pkg.spotplayerCourseIdsText,
        spotplayer_access_limit: pkg.spotplayerAccessLimit ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(`/admin/packages/${pkg.id}`);
    };

    return (
        <>
            <Head title={`ویرایش ${pkg.title}`} />
            <AdminPageHeader
                title="ویرایش بسته"
                description={`${pkg.slug} — ${pkg.ordersCount} سفارش ثبت‌شده`}
                actions={
                    <Button asChild variant="outline" size="sm">
                        <Link href="/admin/packages">بازگشت</Link>
                    </Button>
                }
            />
            <form
                onSubmit={submit}
                className={`${surfaceCardClassName} flex flex-col gap-5`}
            >
                <div className="grid gap-2">
                    <Label htmlFor="title">عنوان</Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        required
                    />
                    <InputError message={errors.title} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="price_toman">قیمت (تومان)</Label>
                    <Input
                        id="price_toman"
                        type="number"
                        min={0}
                        value={data.price_toman}
                        onChange={(e) =>
                            setData('price_toman', Number(e.target.value))
                        }
                        required
                    />
                    <InputError message={errors.price_toman} />
                    <p className="text-xs text-muted">
                        تغییر قیمت فقط روی سفارش‌های جدید اثر می‌گذارد.
                    </p>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="display_order">ترتیب نمایش</Label>
                    <Input
                        id="display_order"
                        type="number"
                        min={0}
                        value={data.display_order}
                        onChange={(e) =>
                            setData('display_order', Number(e.target.value))
                        }
                        required
                    />
                    <InputError message={errors.display_order} />
                </div>
                <div className="flex items-center gap-2">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) =>
                            setData('is_active', checked === true)
                        }
                    />
                    <Label htmlFor="is_active">فعال در فروشگاه</Label>
                </div>
                <InputError message={errors.is_active} />

                <div className="rounded-xl bg-purple-soft/40 p-4 ring-1 ring-purple/15">
                    <h2 className="font-display text-base font-bold text-text">
                        تنظیمات SpotPlayer
                    </h2>
                    <p className="mt-1 text-xs text-muted">
                        شناسه دوره‌ها را با کاما یا خط جدید جدا کنید. محدودیت
                        دسترسی فقط روی خریدهای جدید اعمال می‌شود.
                    </p>
                    <div className="mt-4 grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="spotplayer_course_ids_input">
                                شناسه دوره‌های SpotPlayer
                            </Label>
                            <textarea
                                id="spotplayer_course_ids_input"
                                value={data.spotplayer_course_ids_input}
                                onChange={(e) =>
                                    setData(
                                        'spotplayer_course_ids_input',
                                        e.target.value,
                                    )
                                }
                                rows={4}
                                className="min-h-[6rem] w-full rounded-xl border border-border/70 bg-surface px-3 py-2 text-sm text-text outline-none ring-purple/30 focus:ring-2"
                                dir="ltr"
                                placeholder={'course_id_ch0\ncourse_id_ch1'}
                            />
                            <InputError
                                message={errors.spotplayer_course_ids_input}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="spotplayer_access_limit">
                                محدودیت دسترسی SpotPlayer
                            </Label>
                            <Input
                                id="spotplayer_access_limit"
                                value={data.spotplayer_access_limit}
                                onChange={(e) =>
                                    setData(
                                        'spotplayer_access_limit',
                                        e.target.value,
                                    )
                                }
                                dir="ltr"
                                placeholder="0 یا 1-20"
                            />
                            <InputError
                                message={errors.spotplayer_access_limit}
                            />
                        </div>
                    </div>
                </div>

                <Button
                    type="submit"
                    disabled={processing}
                    className="bg-purple hover:bg-purple/90"
                >
                    ذخیره تغییرات
                </Button>
            </form>
        </>
    );
}
