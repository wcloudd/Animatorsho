import { Head, usePage } from '@inertiajs/react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useAuthSupportFallback } from '@/hooks/use-auth-support-fallback';
import { AUTH_LOGIN_EMAIL_COPY } from '@/lib/auth-form-data';
import { localizeAuthStatus } from '@/lib/auth-validation-messages';
import { cn } from '@/lib/utils';
import { login, register } from '@/routes';
import { store } from '@/routes/login/email';
import { request } from '@/routes/password';

function redirectQueryFromUrl(url: string): { redirect: string } | undefined {
    const query = url.includes('?') ? url.split('?')[1] : '';
    const redirect = new URLSearchParams(query).get('redirect');

    return redirect ? { redirect } : undefined;
}

type Props = {
    status?: string;
    canResetPassword: boolean;
    prefilledEmail?: string | null;
};

export default function LoginEmail({
    status,
    canResetPassword,
    prefilledEmail = null,
}: Props) {
    const copy = AUTH_LOGIN_EMAIL_COPY;
    const localizedStatus = localizeAuthStatus(status);
    const { url } = usePage();
    const redirectQuery = redirectQueryFromUrl(url);
    const { showSupportFallback, onAuthError } = useAuthSupportFallback();

    return (
        <>
            <Head title={copy.headTitle} />

            <AuthPageIntro title={copy.title} subtitle={copy.subtitle} />

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
                                    <Label
                                        htmlFor="email"
                                        className={authLabelClassName}
                                    >
                                        {copy.emailLabel}
                                    </Label>
                                    <Input
                                        id="email"
                                        type="text"
                                        name="email"
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="email"
                                        defaultValue={prefilledEmail ?? ''}
                                        placeholder={copy.emailPlaceholder}
                                        dir="ltr"
                                        className={authFieldClassName}
                                    />
                                    <AuthInputError message={errors.email} />
                                </div>

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
                                                tabIndex={5}
                                            >
                                                {copy.forgotPasswordLabel}
                                            </TextLink>
                                        ) : null}
                                    </div>
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        tabIndex={2}
                                        autoComplete="current-password"
                                        placeholder={copy.passwordPlaceholder}
                                        className={authFieldClassName}
                                    />
                                    <AuthInputError message={errors.password} />
                                </div>

                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        tabIndex={3}
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
                                    tabIndex={4}
                                    disabled={processing}
                                    data-test="login-email-button"
                                >
                                    {processing ? <Spinner /> : null}
                                    {copy.submitLabel}
                                </Button>
                            </div>

                            <div className="flex flex-col items-center gap-2 text-center">
                                <TextLink
                                    href={login(
                                        redirectQuery
                                            ? { query: redirectQuery }
                                            : undefined,
                                    )}
                                    className="text-sm font-bold text-purple"
                                    tabIndex={5}
                                >
                                    {copy.primaryLoginLabel}
                                </TextLink>

                                <div className="flex flex-col items-center gap-1 pt-1">
                                    <TextLink
                                        href={register(
                                            redirectQuery
                                                ? { query: redirectQuery }
                                                : undefined,
                                        )}
                                        className="text-sm font-bold text-purple"
                                        tabIndex={6}
                                    >
                                        {copy.secondaryLinkLabel}
                                    </TextLink>
                                </div>
                            </div>
                        </>
                    )}
                </AuthForm>
            </AuthFormCard>

            <AuthSupportFallbackCard visible={showSupportFallback} />
        </>
    );
}
