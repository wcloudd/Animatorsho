import type { HTMLAttributes } from 'react';
import { localizeAuthError } from '@/lib/auth-validation-messages';
import { cn } from '@/lib/utils';

export function AuthInputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    const localizedMessage = localizeAuthError(message);

    if (!localizedMessage) {
        return null;
    }

    return (
        <p
            {...props}
            className={cn(
                'text-end text-sm font-medium leading-relaxed text-red',
                className,
            )}
        >
            {localizedMessage}
        </p>
    );
}
