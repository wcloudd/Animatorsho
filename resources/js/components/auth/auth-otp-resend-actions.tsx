import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type AuthOtpResendActionsProps = {
    resendSeconds: number;
    resendLabel: string;
    resendWaitLabel: string;
    resending: boolean;
    onResend: () => void;
    'data-test'?: string;
};

export function AuthOtpResendActions({
    resendSeconds,
    resendLabel,
    resendWaitLabel,
    resending,
    onResend,
    'data-test': dataTest,
}: AuthOtpResendActionsProps) {
    if (resendSeconds > 0) {
        return (
            <p className="text-center text-xs font-medium text-muted">
                {resendWaitLabel.replace('{seconds}', String(resendSeconds))}
            </p>
        );
    }

    return (
        <Button
            type="button"
            variant="ghost"
            className="h-auto py-1 text-xs font-bold text-purple"
            disabled={resending}
            onClick={onResend}
            data-test={dataTest}
        >
            {resending ? <Spinner /> : null}
            {resendLabel}
        </Button>
    );
}
