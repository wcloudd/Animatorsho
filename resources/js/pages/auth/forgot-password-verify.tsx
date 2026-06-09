import { Form, Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    AuthFormCard,
    authSubmitButtonClassName,
} from '@/components/auth/auth-form-card';
import { AuthOtpCodeField } from '@/components/auth/auth-otp-code-field';
import { AuthOtpResendActions } from '@/components/auth/auth-otp-resend-actions';
import { AuthPageHeader } from '@/components/auth/auth-page-header';
import { AuthStatusBanner } from '@/components/auth/auth-status-banner';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useOtpResendCountdown } from '@/hooks/use-otp-resend-countdown';
import { AUTH_FORGOT_PASSWORD_VERIFY_COPY } from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';
import { request } from '@/routes/password';
import { resendCode } from '@/routes/password/mobile';
import { store as verifyStore } from '@/routes/password/mobile/verify';

type Props = {
    maskedMobile: string;
    resendAvailableAt?: string | null;
    status?: string;
};

export default function ForgotPasswordVerify({
    maskedMobile,
    resendAvailableAt,
    status,
}: Props) {
    const copy = AUTH_FORGOT_PASSWORD_VERIFY_COPY;
    const subtitle = copy.subtitle.replace('{mobile}', maskedMobile);
    const showSentStatus = status === 'otp-sent';
    const resendSeconds = useOtpResendCountdown(resendAvailableAt);
    const [resending, setResending] = useState(false);

    const handleResend = () => {
        setResending(true);
        router.post(
            resendCode.url(),
            {},
            {
                preserveScroll: true,
                onFinish: () => setResending(false),
            },
        );
    };

    return (
        <>
            <Head title={copy.headTitle} />

            <AuthPageHeader title={copy.title} subtitle={subtitle} />

            {showSentStatus ? (
                <AuthStatusBanner message={copy.sentStatus} />
            ) : null}

            <AuthFormCard>
                <Form {...verifyStore.form()} className="flex flex-col gap-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-4">
                                <AuthOtpCodeField
                                    label={copy.codeLabel}
                                    placeholder={copy.codePlaceholder}
                                    error={errors.code}
                                />

                                <Button
                                    type="submit"
                                    className={cn(authSubmitButtonClassName)}
                                    tabIndex={2}
                                    disabled={processing}
                                    data-test="password-reset-otp-verify-button"
                                >
                                    {processing ? <Spinner /> : null}
                                    {copy.submitLabel}
                                </Button>
                            </div>

                            <AuthOtpResendActions
                                resendSeconds={resendSeconds}
                                resendLabel={copy.resendLabel}
                                resendWaitLabel={copy.resendWaitLabel}
                                resending={resending}
                                onResend={handleResend}
                                data-test="password-reset-otp-resend-button"
                            />

                            <div className="flex flex-col items-center gap-2 text-center">
                                <TextLink
                                    href={request()}
                                    className="text-sm font-bold text-purple"
                                    tabIndex={3}
                                >
                                    {copy.changeMobileLabel}
                                </TextLink>

                                <p className="text-sm font-medium leading-relaxed text-muted">
                                    {copy.emailFallbackHint}
                                </p>
                            </div>
                        </>
                    )}
                </Form>
            </AuthFormCard>

            <AuthSupportFallbackCard />
        </>
    );
}
