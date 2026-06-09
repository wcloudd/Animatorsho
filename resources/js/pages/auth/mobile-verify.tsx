import { Form, Head, router } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { useState } from 'react';
import {
    AuthFormCard,
    authSubmitButtonClassName,
} from '@/components/auth/auth-form-card';
import { AuthOtpCodeField } from '@/components/auth/auth-otp-code-field';
import { AuthOtpResendActions } from '@/components/auth/auth-otp-resend-actions';
import { AuthPageHeader } from '@/components/auth/auth-page-header';
import { AuthSecondaryActionCard } from '@/components/auth/auth-secondary-action-card';
import { AuthStatusBanner } from '@/components/auth/auth-status-banner';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useOtpResendCountdown } from '@/hooks/use-otp-resend-countdown';
import { AUTH_MOBILE_VERIFY_COPY } from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';
import { login, register } from '@/routes';
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
                                href={login()}
                                label={copy.passwordLoginLabel}
                                icon={Lock}
                                tabIndex={3}
                            />

                            <div className="flex flex-col items-center gap-2 text-center">
                                <TextLink
                                    href={create()}
                                    className="text-sm font-bold text-purple"
                                    tabIndex={4}
                                >
                                    {copy.changeMobileLabel}
                                </TextLink>

                                <div className="flex flex-col items-center gap-1">
                                    <p className="text-sm font-medium text-muted">
                                        {copy.registerPrompt}
                                    </p>
                                    <TextLink
                                        href={register()}
                                        className="text-sm font-bold text-purple"
                                        tabIndex={5}
                                    >
                                        {copy.registerLinkLabel}
                                    </TextLink>
                                </div>
                            </div>
                        </>
                    )}
                </Form>
            </AuthFormCard>

            <AuthSupportFallbackCard />
        </>
    );
}
