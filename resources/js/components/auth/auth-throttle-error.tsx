import { AuthInputError } from '@/components/auth/auth-input-error';
import { cn } from '@/lib/utils';

export function AuthThrottleError({
    message,
    className,
}: {
    message?: string;
    className?: string;
}) {
    return (
        <AuthInputError
            message={message}
            className={cn('text-center', className)}
            data-test="auth-throttle-error"
        />
    );
}
