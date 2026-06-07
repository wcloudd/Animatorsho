import { Slot } from '@radix-ui/react-slot';
import type { ComponentProps } from 'react';
import { buttonVariants } from '@/components/ui/button';
import {
    adminButtonStyles,
    type AdminButtonStyle,
} from '@/lib/admin-button-styles';
import { cn } from '@/lib/utils';

type AdminButtonProps = ComponentProps<'button'> & {
    adminVariant?: AdminButtonStyle;
    asChild?: boolean;
    size?: 'default' | 'sm' | 'lg' | 'icon';
};

export function AdminButton({
    adminVariant = 'outline',
    className,
    asChild = false,
    size = 'default',
    ...props
}: AdminButtonProps) {
    const Comp = asChild ? Slot : 'button';

    return (
        <Comp
            data-slot="admin-button"
            className={cn(
                buttonVariants({ variant: 'ghost', size }),
                adminButtonStyles[adminVariant],
                className,
            )}
            {...props}
        />
    );
}
