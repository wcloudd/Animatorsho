import { Form, Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    AuthFormCard,
    authFieldClassName,
    authLabelClassName,
    authSubmitButtonClassName,
} from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthOtpCodeField } from '@/components/auth/auth-otp-code-field';
import { AuthOtpResendActions } from '@/components/auth/auth-otp-resend-actions';
import { AuthPageIntro } from '@/components/auth/auth-page-intro';
import { AuthStatusBanner } from '@/components/auth/auth-status-banner';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useOtpResendCountdown } from '@/hooks/use-otp-resend-countdown';
import { AUTH_REGISTER_VERIFY_COPY } from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';
import { changeMobile, resendCode } from '@/routes/register';
import { store as verifyStore } from '@/routes/register/verify';

type Props = {
    maskedMobile: string;
    resendAvailableAt?: string | null;
    status?: string;
};

export default function RegisterVerify({
    maskedMobile,
    resendAvailableAt,
    status,
}: Props) {
    const copy = AUTH_REGISTER_VERIFY_COPY;
    const subtitle = copy.subtitle.replace('{mobile}', maskedMobile);
    const showSentStatus = status === 'otp-sent';
    const resendSeconds = useOtpResendCountdown(resendAvailableAt);
    const [resending, setResending] = useState(false);
    const [showChangeMobile, setShowChangeMobile] = useState(false);

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
                                    data-test="register-otp-verify-button"
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
                                data-test="register-otp-resend-button"
                            />

                            {!showChangeMobile ? (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="text-sm font-bold text-purple"
                                    onClick={() => setShowChangeMobile(true)}
                                    data-test="register-show-change-mobile"
                                >
                                    {copy.changeMobileLabel}
                                </Button>
                            ) : null}
                        </>
                    )}
                </Form>

                {showChangeMobile ? (
                    <Form
                        {...changeMobile.form()}
                        className="mt-4 flex flex-col gap-3 border-t border-border/80 pt-4"
                    >
                        {({ processing: changingMobile, errors: changeErrors }) => (
                            <>
                                <div className="grid gap-2 text-start">
                                    <Label
                                        htmlFor="change-mobile"
                                        className={authLabelClassName}
                                    >
                                        {copy.changeMobileLabel}
                                    </Label>
                                    <Input
                                        id="change-mobile"
                                        type="tel"
                                        name="mobile"
                                        required
                                        inputMode="numeric"
                                        placeholder="09123456789"
                                        dir="ltr"
                                        className={authFieldClassName}
                                    />
                                    <AuthInputError message={changeErrors.mobile} />
                                </div>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="h-11 w-full rounded-pill text-sm font-bold"
                                    disabled={changingMobile}
                                    data-test="register-change-mobile-button"
                                >
                                    {changingMobile ? <Spinner /> : null}
                                    {copy.changeMobileSubmitLabel}
                                </Button>
                            </>
                        )}
                    </Form>
                ) : null}
            </AuthFormCard>

            <AuthSupportFallbackCard />
        </>
    );
}
