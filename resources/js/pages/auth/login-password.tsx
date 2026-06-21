import { Head, router } from '@inertiajs/react';
import { AuthForm } from '@/components/auth/auth-form';
import {
    AuthFormCard,
    authFieldClassName,
    authLabelClassName,
    authSubmitButtonClassName,
} from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthThrottleError } from '@/components/auth/auth-throttle-error';
import { AuthPageIntro } from '@/components/auth/auth-page-intro';
import { AuthStatusBanner } from '@/components/auth/auth-status-banner';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useAuthSupportFallback } from '@/hooks/use-auth-support-fallback';
import { AUTH_LOGIN_PASSWORD_COPY } from '@/lib/auth-form-data';
import { localizeAuthStatus } from '@/lib/auth-validation-messages';
import { cn } from '@/lib/utils';
import { sendCodeFromSession } from '@/routes/auth/mobile';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { MessageSquare } from 'lucide-react';

type Props = {
    maskedMobile: string;
    status?: string;
    canResetPassword: boolean;
};

export default function LoginPassword({
    maskedMobile,
    status,
    canResetPassword,
}: Props) {
    const copy = AUTH_LOGIN_PASSWORD_COPY;
    const localizedStatus = localizeAuthStatus(status);
    const [subtitleBefore, subtitleAfter] = copy.subtitle.split('{mobile}');
    const subtitle = (
        <>
            {subtitleBefore}
            <bdi dir="ltr">{maskedMobile}</bdi>
            {subtitleAfter}
        </>
    );
    const { showSupportFallback, onAuthError } = useAuthSupportFallback();

    return (
        <>
            <Head title={copy.headTitle} />

            <AuthPageIntro title={copy.title} subtitle={subtitle} />

            {localizedStatus ? (
                <AuthStatusBanner message={localizedStatus} />
            ) : null}

            <AuthFormCard>
                <AuthForm
                    {...store.form()}
                    resetOnSuccess={['password']}
                    onError={onAuthError}
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <AuthThrottleError message={errors.throttle} />
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <div className="flex items-center justify-between gap-2">
                                        <Label
                                            htmlFor="password"
                                            className={authLabelClassName}
                                        >
                                            {copy.passwordLabel}
                                        </Label>
                                        {canResetPassword ? (
                                            <TextLink
                                                href={request()}
                                                className="text-xs font-bold text-purple"
                                                tabIndex={3}
                                            >
                                                {copy.forgotPasswordLabel}
                                            </TextLink>
                                        ) : null}
                                    </div>
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="current-password"
                                        placeholder={copy.passwordPlaceholder}
                                        className={authFieldClassName}
                                    />
                                    <AuthInputError message={errors.password} />
                                    <AuthInputError message={errors.mobile} />
                                </div>

                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        tabIndex={2}
                                    />
                                    <Label
                                        htmlFor="remember"
                                        className="text-sm font-medium text-text"
                                    >
                                        {copy.rememberLabel}
                                    </Label>
                                </div>

                                <Button
                                    type="submit"
                                    className={cn(authSubmitButtonClassName)}
                                    tabIndex={3}
                                    disabled={processing}
                                    data-test="login-password-button"
                                >
                                    {processing ? <Spinner /> : null}
                                    {copy.submitLabel}
                                </Button>
                            </div>

                            <button
                                type="button"
                                tabIndex={4}
                                data-test="login-password-otp-link"
                                onClick={() =>
                                    router.post(sendCodeFromSession.url())
                                }
                                className="flex h-12 w-full items-center justify-start gap-2 rounded-2xl bg-purple-soft/50 px-4 text-sm font-bold text-text shadow-xs ring-1 ring-border transition-colors hover:bg-purple-soft/80"
                            >
                                <MessageSquare
                                    className="size-4 shrink-0 text-muted"
                                    aria-hidden
                                />
                                <span>{copy.otpLoginLabel}</span>
                            </button>
                        </>
                    )}
                </AuthForm>
            </AuthFormCard>

            <AuthSupportFallbackCard visible={showSupportFallback} />
        </>
    );
}
