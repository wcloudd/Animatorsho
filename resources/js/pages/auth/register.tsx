import { usePage } from '@inertiajs/react';
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
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { SeoHead } from '@/components/seo/seo-head';
import { useAuthSupportFallback } from '@/hooks/use-auth-support-fallback';
import {
    AUTH_REGISTER_COPY,
    AUTH_REGISTER_TRUST_NOTE,
} from '@/lib/auth-form-data';
import { PUBLIC_PAGE_SEO, canonicalFromPath } from '@/lib/seo';
import { cn } from '@/lib/utils';
import type { SharedPageProps } from '@/types/seo';
import { login } from '@/routes';
import { store } from '@/routes/register';

function redirectQueryFromUrl(url: string): { redirect: string } | undefined {
    const query = url.includes('?') ? url.split('?')[1] : '';
    const redirect = new URLSearchParams(query).get('redirect');

    return redirect ? { redirect } : undefined;
}

type PendingRegistration = {
    name: string;
    username: string;
    mobile: string;
    email: string | null;
};

type Props = {
    passwordRules: string;
    pendingRegistration?: PendingRegistration | null;
    pendingMobile?: string | null;
    mobileLocked?: boolean;
};

export default function Register({
    passwordRules,
    pendingRegistration = null,
    pendingMobile = null,
    mobileLocked = false,
}: Props) {
    const copy = AUTH_REGISTER_COPY;
    const page = usePage<SharedPageProps>();
    const redirectQuery = redirectQueryFromUrl(page.url);
    const meta = PUBLIC_PAGE_SEO.register;
    const { showSupportFallback, onAuthError } = useAuthSupportFallback();

    return (
        <>
            <SeoHead
                title={meta.title}
                description={meta.description}
                canonical={canonicalFromPath(page.props.appUrl, '/register')}
            />

            <AuthPageIntro title={copy.title} subtitle={copy.subtitle} />

            <AuthFormCard>
                <AuthForm
                    {...store.form()}
                    resetOnSuccess={['password', 'password_confirmation']}
                    disableWhileProcessing
                    onError={onAuthError}
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <AuthThrottleError message={errors.throttle} />
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="name"
                                        className={authLabelClassName}
                                    >
                                        {copy.nameLabel}
                                    </Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="name"
                                        name="name"
                                        defaultValue={
                                            pendingRegistration?.name ?? ''
                                        }
                                        placeholder={copy.namePlaceholder}
                                        className={authFieldClassName}
                                    />
                                    <AuthInputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="username"
                                        className={authLabelClassName}
                                    >
                                        {copy.usernameLabel}
                                    </Label>
                                    <Input
                                        id="username"
                                        type="text"
                                        tabIndex={2}
                                        autoComplete="username"
                                        name="username"
                                        defaultValue={
                                            pendingRegistration?.username ?? ''
                                        }
                                        placeholder={copy.usernamePlaceholder}
                                        dir="ltr"
                                        onChange={(event) => {
                                            event.target.value =
                                                event.target.value.toLowerCase();
                                        }}
                                        className={authFieldClassName}
                                    />
                                    <p className="text-xs font-medium leading-relaxed text-muted">
                                        {copy.usernameHint}
                                    </p>
                                    <AuthInputError
                                        message={errors.username}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="mobile"
                                        className={authLabelClassName}
                                    >
                                        {copy.mobileLabel}
                                    </Label>
                                    {mobileLocked ? (
                                        <>
                                            <Input
                                                id="mobile"
                                                type="tel"
                                                readOnly
                                                tabIndex={3}
                                                value={
                                                    pendingMobile ??
                                                    pendingRegistration?.mobile ??
                                                    ''
                                                }
                                                dir="ltr"
                                                className={cn(
                                                    authFieldClassName,
                                                    'bg-purple-soft/40',
                                                )}
                                            />
                                            <input
                                                type="hidden"
                                                name="mobile"
                                                value={
                                                    pendingMobile ??
                                                    pendingRegistration?.mobile ??
                                                    ''
                                                }
                                            />
                                        </>
                                    ) : (
                                        <Input
                                            id="mobile"
                                            type="tel"
                                            tabIndex={3}
                                            autoComplete="tel"
                                            inputMode="numeric"
                                            name="mobile"
                                            defaultValue={
                                                pendingRegistration?.mobile ??
                                                pendingMobile ??
                                                ''
                                            }
                                            placeholder={copy.mobilePlaceholder}
                                            dir="ltr"
                                            className={authFieldClassName}
                                        />
                                    )}
                                    <AuthInputError message={errors.mobile} />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="password"
                                        className={authLabelClassName}
                                    >
                                        {copy.passwordLabel}
                                    </Label>
                                    <PasswordInput
                                        id="password"
                                        tabIndex={4}
                                        autoComplete="new-password"
                                        name="password"
                                        placeholder={copy.passwordPlaceholder}
                                        passwordrules={passwordRules}
                                        className={authFieldClassName}
                                    />
                                    <AuthInputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="password_confirmation"
                                        className={authLabelClassName}
                                    >
                                        {copy.passwordConfirmLabel}
                                    </Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        tabIndex={5}
                                        autoComplete="new-password"
                                        name="password_confirmation"
                                        placeholder={
                                            copy.passwordConfirmPlaceholder
                                        }
                                        passwordrules={passwordRules}
                                        className={authFieldClassName}
                                    />
                                    <AuthInputError
                                        message={errors.password_confirmation}
                                    />
                                </div>

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
                                        tabIndex={6}
                                        autoComplete="email"
                                        name="email"
                                        defaultValue={
                                            pendingRegistration?.email ?? ''
                                        }
                                        placeholder={copy.emailPlaceholder}
                                        dir="ltr"
                                        className={authFieldClassName}
                                    />
                                    <p className="text-xs font-medium leading-relaxed text-muted">
                                        {copy.emailHint}
                                    </p>
                                    <AuthInputError message={errors.email} />
                                </div>

                                <Button
                                    type="submit"
                                    className={cn(authSubmitButtonClassName)}
                                    tabIndex={7}
                                    data-test="register-user-button"
                                >
                                    {processing ? <Spinner /> : null}
                                    {copy.submitLabel}
                                </Button>
                            </div>

                            <div className="flex flex-col items-center gap-1 text-center">
                                <p className="text-sm font-medium text-muted">
                                    {copy.secondaryPrompt}
                                </p>
                                <TextLink
                                    href={login(
                                        redirectQuery
                                            ? { query: redirectQuery }
                                            : undefined,
                                    )}
                                    className="text-sm font-bold text-purple"
                                    tabIndex={8}
                                >
                                    {copy.secondaryLinkLabel}
                                </TextLink>
                            </div>
                        </>
                    )}
                </AuthForm>
            </AuthFormCard>

            <p className="text-center text-xs font-medium leading-relaxed text-muted">
                {AUTH_REGISTER_TRUST_NOTE}
            </p>

            <AuthSupportFallbackCard visible={showSupportFallback} />
        </>
    );
}
