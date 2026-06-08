import { Form, Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { AuthFormCard, authFieldClassName, authLabelClassName } from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthPageHeader } from '@/components/auth/auth-page-header';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
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

function secondsUntil(isoDate: string): number {
    const target = new Date(isoDate).getTime();
    const diff = Math.ceil((target - Date.now()) / 1000);

    return diff > 0 ? diff : 0;
}

export default function ForgotPasswordVerify({
    maskedMobile,
    resendAvailableAt,
    status,
}: Props) {
    const copy = AUTH_FORGOT_PASSWORD_VERIFY_COPY;
    const subtitle = copy.subtitle.replace('{mobile}', maskedMobile);
    const showSentStatus = status === 'otp-sent';
    const [resendSeconds, setResendSeconds] = useState(() =>
        resendAvailableAt ? secondsUntil(resendAvailableAt) : 0,
    );
    const [resending, setResending] = useState(false);

    useEffect(() => {
        if (!resendAvailableAt) {
            setResendSeconds(0);

            return;
        }

        setResendSeconds(secondsUntil(resendAvailableAt));

        const interval = window.setInterval(() => {
            setResendSeconds(secondsUntil(resendAvailableAt));
        }, 1000);

        return () => window.clearInterval(interval);
    }, [resendAvailableAt]);

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
                <p className="rounded-2xl bg-green-soft px-4 py-3 text-center text-sm font-medium leading-relaxed text-green">
                    {copy.sentStatus}
                </p>
            ) : null}

            <AuthFormCard>
                <Form {...verifyStore.form()} className="flex flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="code" className={authLabelClassName}>
                                        {copy.codeLabel}
                                    </Label>
                                    <Input
                                        id="code"
                                        type="text"
                                        name="code"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="one-time-code"
                                        inputMode="numeric"
                                        pattern="[0-9]*"
                                        maxLength={6}
                                        placeholder={copy.codePlaceholder}
                                        dir="ltr"
                                        className={cn(authFieldClassName, 'tracking-widest text-center')}
                                    />
                                    <AuthInputError message={errors.code} />
                                </div>

                                <Button
                                    type="submit"
                                    className={cn(
                                        'btn-cta-green h-12 w-full rounded-pill text-sm font-bold text-white',
                                    )}
                                    tabIndex={2}
                                    disabled={processing}
                                    data-test="password-reset-otp-verify-button"
                                >
                                    {processing ? <Spinner /> : null}
                                    {copy.submitLabel}
                                </Button>
                            </div>

                            <div className="flex flex-col items-center gap-3 text-center">
                                {resendSeconds > 0 ? (
                                    <p className="text-sm font-medium text-muted">
                                        {copy.resendWaitLabel.replace(
                                            '{seconds}',
                                            String(resendSeconds),
                                        )}
                                    </p>
                                ) : (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="text-sm font-bold text-purple"
                                        disabled={resending}
                                        onClick={handleResend}
                                        data-test="password-reset-otp-resend-button"
                                    >
                                        {resending ? <Spinner /> : null}
                                        {copy.resendLabel}
                                    </Button>
                                )}

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
