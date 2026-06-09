import { Form, usePage } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';
import {
    AuthFormCard,
    authFieldClassName,
    authLabelClassName,
    authSubmitButtonClassName,
} from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthPageIntro } from '@/components/auth/auth-page-intro';
import { AuthSecondaryActionCard } from '@/components/auth/auth-secondary-action-card';
import { AuthStatusBanner } from '@/components/auth/auth-status-banner';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { SeoHead } from '@/components/seo/seo-head';
import { AUTH_LOGIN_COPY } from '@/lib/auth-form-data';
import { localizeAuthStatus } from '@/lib/auth-validation-messages';
import { PUBLIC_PAGE_SEO, canonicalFromPath } from '@/lib/seo';
import { cn } from '@/lib/utils';
import type { SharedPageProps } from '@/types/seo';
import { create as mobileAuthCreate } from '@/routes/auth/mobile';
import { register } from '@/routes';
import { email as loginEmail, store } from '@/routes/login';
import { request } from '@/routes/password';

function redirectQueryFromUrl(url: string): { redirect: string } | undefined {
    const query = url.includes('?') ? url.split('?')[1] : '';
    const redirect = new URLSearchParams(query).get('redirect');

    return redirect ? { redirect } : undefined;
}

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    const copy = AUTH_LOGIN_COPY;
    const localizedStatus = localizeAuthStatus(status);
    const page = usePage<SharedPageProps>();
    const redirectQuery = redirectQueryFromUrl(page.url);
    const meta = PUBLIC_PAGE_SEO.login;

    return (
        <>
            <SeoHead
                title={meta.title}
                description={meta.description}
                canonical={canonicalFromPath(page.props.appUrl, '/login')}
            />

            <AuthPageIntro title={copy.title} subtitle={copy.subtitle} />

            {localizedStatus ? (
                <AuthStatusBanner message={localizedStatus} />
            ) : null}

            <AuthFormCard>
                <Form
                    {...store.form()}
                    resetOnSuccess={['password']}
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="mobile"
                                        className={authLabelClassName}
                                    >
                                        {copy.mobileLabel}
                                    </Label>
                                    <Input
                                        id="mobile"
                                        type="tel"
                                        name="mobile"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="tel"
                                        placeholder={copy.mobilePlaceholder}
                                        dir="ltr"
                                        className={authFieldClassName}
                                    />
                                    <AuthInputError message={errors.mobile} />
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
                                        required
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
                                    data-test="login-button"
                                >
                                    {processing ? <Spinner /> : null}
                                    {copy.submitLabel}
                                </Button>
                            </div>

                            <AuthSecondaryActionCard
                                href={mobileAuthCreate(
                                    redirectQuery
                                        ? { query: redirectQuery }
                                        : undefined,
                                )}
                                label={copy.otpLoginLabel}
                                icon={MessageSquare}
                                tabIndex={5}
                            />

                            <div className="flex flex-col items-center gap-2 text-center">
                                <TextLink
                                    href={loginEmail(
                                        redirectQuery
                                            ? { query: redirectQuery }
                                            : undefined,
                                    )}
                                    className="text-xs font-medium text-muted"
                                    tabIndex={6}
                                >
                                    {copy.legacyEmailLoginLabel}
                                </TextLink>

                                <div className="flex flex-col items-center gap-1 pt-1">
                                    <p className="text-sm font-medium text-muted">
                                        {copy.secondaryPrompt}
                                    </p>
                                    <TextLink
                                        href={register(
                                            redirectQuery
                                                ? { query: redirectQuery }
                                                : undefined,
                                        )}
                                        className="text-sm font-bold text-purple"
                                        tabIndex={7}
                                    >
                                        {copy.secondaryLinkLabel}
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
