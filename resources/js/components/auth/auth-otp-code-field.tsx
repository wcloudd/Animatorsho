import { AuthInputError } from '@/components/auth/auth-input-error';
import { authFieldClassName, authLabelClassName } from '@/components/auth/auth-form-card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type AuthOtpCodeFieldProps = {
    id?: string;
    label: string;
    placeholder: string;
    error?: string;
    tabIndex?: number;
    'data-test'?: string;
};

export function AuthOtpCodeField({
    id = 'code',
    label,
    placeholder,
    error,
    tabIndex = 1,
    'data-test': dataTest,
}: AuthOtpCodeFieldProps) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id} className={authLabelClassName}>
                {label}
            </Label>
            <Input
                id={id}
                type="text"
                name="code"
                required
                autoFocus
                tabIndex={tabIndex}
                autoComplete="one-time-code"
                inputMode="numeric"
                pattern="[0-9]*"
                maxLength={6}
                placeholder={placeholder}
                dir="ltr"
                data-test={dataTest}
                className={cn(
                    authFieldClassName,
                    'text-center text-lg tracking-[0.35em]',
                )}
            />
            <AuthInputError message={error} />
        </div>
    );
}
