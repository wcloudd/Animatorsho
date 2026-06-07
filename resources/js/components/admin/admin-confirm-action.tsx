import { Link } from '@inertiajs/react';
import { AdminButton } from '@/components/admin/admin-button';
import type { AdminButtonStyle } from '@/lib/admin-button-styles';

type AdminConfirmActionProps = {
    actionKey: string | number;
    activeKey: string | number | null;
    onActivate: (key: string | number) => void;
    onCancel: () => void;
    triggerLabel: string;
    confirmLabel: string;
    cancelLabel?: string;
    message?: string;
    href: string;
    method?: 'post' | 'put' | 'patch' | 'delete';
    triggerVariant?: AdminButtonStyle;
    confirmVariant?: AdminButtonStyle;
    size?: 'default' | 'sm' | 'lg' | 'icon';
};

export function AdminConfirmAction({
    actionKey,
    activeKey,
    onActivate,
    onCancel,
    triggerLabel,
    confirmLabel,
    cancelLabel = 'انصراف',
    message,
    href,
    method = 'post',
    triggerVariant = 'dangerOutline',
    confirmVariant = 'danger',
    size = 'sm',
}: AdminConfirmActionProps) {
    const isConfirming = activeKey === actionKey;

    if (!isConfirming) {
        return (
            <AdminButton
                type="button"
                size={size}
                adminVariant={triggerVariant}
                onClick={() => onActivate(actionKey)}
            >
                {triggerLabel}
            </AdminButton>
        );
    }

    return (
        <div className="flex w-full basis-full flex-col gap-2 rounded-xl bg-red-soft/40 p-3 ring-1 ring-red/20">
            {message ? (
                <p className="text-xs font-medium leading-relaxed text-text">
                    {message}
                </p>
            ) : null}
            <div className="flex flex-wrap gap-2">
                <AdminButton asChild size={size} adminVariant={confirmVariant}>
                    <Link
                        href={href}
                        method={method}
                        as="button"
                        preserveScroll
                    >
                        {confirmLabel}
                    </Link>
                </AdminButton>
                <AdminButton
                    type="button"
                    size={size}
                    adminVariant="outline"
                    onClick={onCancel}
                >
                    {cancelLabel}
                </AdminButton>
            </div>
        </div>
    );
}
