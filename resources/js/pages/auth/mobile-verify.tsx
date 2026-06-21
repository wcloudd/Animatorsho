import { Head, router } from '@inertiajs/react';
import { AuthForm } from '@/components/auth/auth-form';
import { Lock } from 'lucide-react';
import { useState } from 'react';
import {
    AuthFormCard,
    authSubmitButtonClassName,
} from '@/components/auth/auth-form-card';
import { AuthOtpCodeField } from '@/components/auth/auth-otp-code-field';
import { AuthThrottleError } from '@/components/auth/auth-throttle-error';
import { AuthOtpResendActions } from '@/components/auth/auth-otp-resend-actions';
import { AuthPageIntro } from '@/components/auth/auth-page-intro';
import { AuthSecondaryActionCard } from '@/components/auth/auth-secondary-action-card';
import { AuthStatusBanner } from '@/components/auth/auth-status-banner';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useAuthSupportFallback } from '@/hooks/use-auth-support-fallback';
import { useOtpResendCountdown } from '@/hooks/use-otp-resend-countdown';
import { AUTH_MOBILE_VERIFY_COPY } from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';
import { password as loginPassword } from '@/routes/login';
import { create, resendCode } from '@/routes/auth/mobile';
import { store as verifyStore } from '@/routes/auth/mobile/verify';

type Props = {
    maskedMobile: string;
    resendAvailableAt?: string | null;
    status?: string;
};

export default function MobileVerifyAuth({
    maskedMobile,
    resendAvailableAt,
    status,
}: Props) {
    const copy = AUTH_MOBILE_VERIFY_COPY;
    const [subtitleBefore, subtitleAfter] = copy.subtitle.split('{mobile}');
    const subtitle = (
        <>
            {subtitleBefore}
            <bdi dir="ltr">{maskedMobile}</bdi>
            {subtitleAfter}
        </>
    );
    const showSentStatus = status === 'otp-sent';
    const resendSeconds = useOtpResendCountdown(resendAvailableAt);
    const [resending, setResending] = useState(false);
    const { showSupportFallback, onAuthError } = useAuthSupportFallback();

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

            <AuthPageIntro title={copy.title} subtitle={subtitle} />

            {showSentStatus ? (
                <AuthStatusBanner message={copy.sentStatus} />
            ) : null}

            <AuthFormCard>
                <AuthForm
                    {...verifyStore.form()}
                    onError={onAuthError}
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <AuthThrottleError message={errors.throttle} />
                            <div className="grid gap-4">
                                <AuthOtpCodeField
                                    label={copy.codeLabel}
                                    placeholder={copy.codePlaceholder}
                                    error={errors.code}
                                />

                                <TextLink
                                    href={create()}
                                    className="self-start text-xs font-bold text-muted transition-colors hover:text-purple"
                                    tabIndex={2}
                                    data-test="mobile-otp-change-mobile"
                                >
                                    {copy.changeMobileLabel}
                                </TextLink>

                                <Button
                                    type="submit"
                                    className={cn(authSubmitButtonClassName)}
                                    tabIndex={3}
                                    disabled={processing}
                                    data-test="mobile-otp-verify-button"
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
                                data-test="mobile-otp-resend-button"
                            />

                            <AuthSecondaryActionCard
                                href={loginPassword()}
                                label={copy.passwordLoginLabel}
                                icon={Lock}
                                alignEnd
                                tabIndex={4}
                                data-test="mobile-otp-password-login"
                            />
                        </>
                    )}
                </AuthForm>
            </AuthFormCard>

            <AuthSupportFallbackCard visible={showSupportFallback} />
        </>
    );
}
