import { Form, Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { AuthFormCard, authFieldClassName, authLabelClassName } from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthPageHeader } from '@/components/auth/auth-page-header';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { AUTH_REGISTER_VERIFY_COPY } from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';
import { changeMobile, resendCode } from '@/routes/register';
import { store as verifyStore } from '@/routes/register/verify';

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

export default function RegisterVerify({
    maskedMobile,
    resendAvailableAt,
    status,
}: Props) {
    const copy = AUTH_REGISTER_VERIFY_COPY;
    const subtitle = copy.subtitle.replace('{mobile}', maskedMobile);
    const showSentStatus = status === 'otp-sent';
    const [resendSeconds, setResendSeconds] = useState(() =>
        resendAvailableAt ? secondsUntil(resendAvailableAt) : 0,
    );
    const [resending, setResending] = useState(false);
    const [showChangeMobile, setShowChangeMobile] = useState(false);

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
                                        className={cn(
                                            authFieldClassName,
                                            'tracking-widest text-center',
                                        )}
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
                                    data-test="register-otp-verify-button"
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
                                        data-test="register-otp-resend-button"
                                    >
                                        {resending ? <Spinner /> : null}
                                        {copy.resendLabel}
                                    </Button>
                                )}

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
                            </div>
                        </>
                    )}
                </Form>

                {showChangeMobile ? (
                    <Form
                        {...changeMobile.form()}
                        className="mt-5 flex flex-col gap-3 border-t border-border pt-5"
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
