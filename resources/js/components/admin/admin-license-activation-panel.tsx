import { useForm } from '@inertiajs/react';
import { AdminActionRow } from '@/components/admin/admin-action-row';
import { AdminButton } from '@/components/admin/admin-button';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AdminLicenseListItem } from '@/types/admin';

type AdminLicenseActivationPanelProps = {
    license: AdminLicenseListItem;
};

export function AdminLicenseActivationPanel({
    license,
}: AdminLicenseActivationPanelProps) {
    const isReactivation = license.statusValue === 'revoked';

    const { data, setData, post, processing, errors, reset } = useForm({
        license_key: license.licenseKey ?? '',
    });

    return (
        <AdminActionRow
            bordered={false}
            className="rounded-xl bg-gold-soft/40 p-3 ring-1 ring-gold/20"
        >
            <p className="text-xs font-bold text-gold">
                {isReactivation
                    ? 'فعال‌سازی مجدد لایسنس'
                    : 'فعال‌سازی دستی لایسنس'}
            </p>
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    post(`/admin/licenses/${license.id}/activate`, {
                        preserveScroll: true,
                        onSuccess: () => reset(),
                    });
                }}
                className="flex flex-col gap-2"
            >
                <Label htmlFor={`license_key_${license.id}`}>
                    کلید لایسنس SpotPlayer
                </Label>
                {license.licenseKey ? (
                    <p className="text-xs font-medium text-muted">
                        کلید فعلی در پایگاه داده موجود است. در صورت نیاز
                        می‌توانید آن را جایگزین کنید.
                    </p>
                ) : null}
                <Input
                    id={`license_key_${license.id}`}
                    value={data.license_key}
                    onChange={(e) => setData('license_key', e.target.value)}
                    placeholder="کلید را از پنل SpotPlayer وارد کنید"
                    required
                    dir="ltr"
                    className="font-mono"
                />
                <InputError message={errors.license_key} />
                <AdminButton
                    type="submit"
                    size="sm"
                    adminVariant="success"
                    disabled={processing}
                >
                    {isReactivation ? 'فعال‌سازی مجدد' : 'فعال‌سازی دستی'}
                </AdminButton>
            </form>
        </AdminActionRow>
    );
}
