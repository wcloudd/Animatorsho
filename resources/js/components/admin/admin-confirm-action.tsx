import { Link } from '@inertiajs/react';
import { AdminButton } from '@/components/admin/admin-button';
import { adminCalloutStyles } from '@/components/admin/admin-callout';
import type { AdminButtonStyle } from '@/lib/admin-button-styles';
import { cn } from '@/lib/utils';

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
        <div
            className={cn(
                'flex w-full basis-full flex-col gap-2 p-3',
                adminCalloutStyles.error.box,
            )}
        >
            {message ? (
                <p className={adminCalloutStyles.error.title}>{message}</p>
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
