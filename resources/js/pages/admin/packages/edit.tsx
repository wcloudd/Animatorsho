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
