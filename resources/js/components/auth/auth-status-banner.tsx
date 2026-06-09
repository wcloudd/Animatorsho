import { cn } from '@/lib/utils';

type Variant = 'success' | 'warning';

type AuthStatusBannerProps = {
    message: string;
    variant?: Variant;
};

const variantClassName: Record<Variant, string> = {
    success: 'bg-green-soft text-green',
    warning: 'bg-gold-soft text-text',
};

export function AuthStatusBanner({
    message,
    variant = 'success',
}: AuthStatusBannerProps) {
    return (
        <p
            className={cn(
                'rounded-2xl px-4 py-3 text-center text-sm font-medium leading-relaxed',
                variantClassName[variant],
            )}
        >
            {message}
        </p>
    );
}
