import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminButton } from '@/components/admin/admin-button';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    AdminConsultationListItem,
    AdminStatusOption,
} from '@/types/admin';
import { cn } from '@/lib/utils';

const textareaClassName = cn(
    'border-input bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm text-start shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
    'min-h-[80px]',
);

type AdminConsultationUpdatePanelProps = {
    consultation: AdminConsultationListItem;
    statusOptions: AdminStatusOption[];
};

export function AdminConsultationUpdatePanel({
    consultation,
    statusOptions,
}: AdminConsultationUpdatePanelProps) {
    const { data, setData, patch, processing, errors } = useForm({
        status: consultation.statusValue,
        admin_note: consultation.adminNote ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(`/admin/consultations/${consultation.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <form
            onSubmit={submit}
            className="flex w-full basis-full flex-col gap-3 rounded-2xl border border-[#e8e0f0] bg-bg p-4"
        >
            <p className="text-sm font-bold text-text">مدیریت درخواست</p>
            <div className="grid gap-2">
                <Label htmlFor={`consultation-status-${consultation.id}`}>
                    وضعیت
                </Label>
                <Select
                    value={data.status}
                    onValueChange={(value) => setData('status', value)}
                >
                    <SelectTrigger
                        id={`consultation-status-${consultation.id}`}
                        className="h-10 w-full"
                    >
                        <SelectValue placeholder="انتخاب وضعیت" />
                    </SelectTrigger>
                    <SelectContent position="popper">
                        {statusOptions.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.status} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={`consultation-admin-note-${consultation.id}`}>
                    یادداشت ادمین
                </Label>
                <textarea
                    id={`consultation-admin-note-${consultation.id}`}
                    value={data.admin_note}
                    onChange={(event) =>
                        setData('admin_note', event.target.value)
                    }
                    rows={3}
                    className={textareaClassName}
                />
                <InputError message={errors.admin_note} />
            </div>
            <AdminButton
                type="submit"
                size="sm"
                adminVariant="brand"
                disabled={processing}
            >
                ذخیره تغییرات
            </AdminButton>
        </form>
    );
}
