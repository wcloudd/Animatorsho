import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { AdminButton } from '@/components/admin/admin-button';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AdminOrderListItem } from '@/types/admin';

type AdminOrderCustomerEditProps = {
    order: AdminOrderListItem;
};

export function AdminOrderCustomerEdit({ order }: AdminOrderCustomerEditProps) {
    const [isEditing, setIsEditing] = useState(false);
    const { data, setData, patch, processing, errors, reset } = useForm({
        customer_name: order.customerName ?? '',
        customer_mobile: order.customerMobile ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(`/admin/orders/${order.id}/customer`, {
            preserveScroll: true,
            onSuccess: () => setIsEditing(false),
        });
    };

    const handleCancel = () => {
        reset();
        setIsEditing(false);
    };

    if (!isEditing) {
        return (
            <AdminButton
                type="button"
                size="sm"
                adminVariant="outline"
                onClick={() => setIsEditing(true)}
            >
                ویرایش اطلاعات تماس
            </AdminButton>
        );
    }

    return (
        <form
            onSubmit={submit}
            className="flex w-full flex-col gap-3 rounded-2xl border border-[#e8e0f0] bg-bg p-4"
        >
            <p className="text-sm font-bold text-text">ویرایش اطلاعات تماس سفارش</p>
            <div className="grid gap-2">
                <Label htmlFor={`customer-name-${order.id}`}>نام سفارش</Label>
                <Input
                    id={`customer-name-${order.id}`}
                    value={data.customer_name}
                    onChange={(event) =>
                        setData('customer_name', event.target.value)
                    }
                    required
                />
                <InputError message={errors.customer_name} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={`customer-mobile-${order.id}`}>
                    موبایل سفارش
                </Label>
                <Input
                    id={`customer-mobile-${order.id}`}
                    type="tel"
                    dir="ltr"
                    value={data.customer_mobile}
                    onChange={(event) =>
                        setData('customer_mobile', event.target.value)
                    }
                    required
                />
                <InputError message={errors.customer_mobile} />
            </div>
            <div className="flex flex-wrap gap-2">
                <AdminButton
                    type="submit"
                    size="sm"
                    adminVariant="brand"
                    disabled={processing}
                >
                    ذخیره
                </AdminButton>
                <AdminButton
                    type="button"
                    size="sm"
                    adminVariant="outline"
                    disabled={processing}
                    onClick={handleCancel}
                >
                    انصراف
                </AdminButton>
            </div>
        </form>
    );
}
