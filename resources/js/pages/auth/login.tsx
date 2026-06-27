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
import { LoginBrandTitle } from '@/components/auth/login-brand-title';
import { AuthStatusBanner } from '@/components/auth/auth-status-banner';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { SeoHead } from '@/components/seo/seo-head';
import { useAuthSupportFallback } from '@/hooks/use-auth-support-fallback';
import { AUTH_LOGIN_IDENTIFIER_COPY } from '@/lib/auth-form-data';
import { CHECKOUT_PURCHASE_RULES_URL } from '@/lib/checkout-urls';
import { localizeAuthStatus } from '@/lib/auth-validation-messages';
import { PUBLIC_PAGE_SEO, canonicalFromPath } from '@/lib/seo';
import { cn } from '@/lib/utils';
import type { SharedPageProps } from '@/types/seo';
import { identifier } from '@/routes/login';

function redirectQueryFromUrl(url: string): { redirect: string } | undefined {
    const query = url.includes('?') ? url.split('?')[1] : '';
    const redirect = new URLSearchParams(query).get('redirect');

    return redirect ? { redirect } : undefined;
}

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status }: Props) {
    const copy = AUTH_LOGIN_IDENTIFIER_COPY;
    const localizedStatus = localizeAuthStatus(status);
    const page = usePage<SharedPageProps>();
    const redirectQuery = redirectQueryFromUrl(page.url);
    const meta = PUBLIC_PAGE_SEO.login;
    const { showSupportFallback, onAuthError } = useAuthSupportFallback();

    return (
        <>
            <SeoHead
                title={meta.title}
                description={meta.description}
                canonical={canonicalFromPath(page.props.appUrl, '/login')}
            />

            <AuthPageIntro title={copy.title} mark={<LoginBrandTitle />} />

            {localizedStatus ? (
                <AuthStatusBanner message={localizedStatus} />
            ) : null}

            <AuthFormCard>
                <AuthForm
                    {...identifier.form(
                        redirectQuery ? { query: redirectQuery } : undefined,
                    )}
                    onError={onAuthError}
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <AuthThrottleError message={errors.throttle} />
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="identifier"
                                        className={authLabelClassName}
                                    >
                                        {copy.identifierLabel}
                                    </Label>
                                    <Input
                                        id="identifier"
                                        type="text"
                                        name="identifier"
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="username"
                                        placeholder={copy.identifierPlaceholder}
                                        dir="ltr"
                                        className={cn(
                                            authFieldClassName,
                                            'text-right',
                                        )}
                                    />
                                    <AuthInputError
                                        message={errors.identifier}
                                    />
                                </div>

                                <p className="text-center text-xs font-medium leading-relaxed text-muted">
                                    {copy.termsNoteBeforeLink}
                                    <TextLink
                                        href={CHECKOUT_PURCHASE_RULES_URL}
                                        className="text-xs font-bold text-purple"
                                        tabIndex={2}
                                    >
                                        {copy.termsLinkLabel}
                                    </TextLink>
                                    {copy.termsNoteAfterLink}
                                </p>

                                <Button
                                    type="submit"
                                    className={cn(authSubmitButtonClassName)}
                                    tabIndex={3}
                                    disabled={processing}
                                    data-test="login-identifier-button"
                                >
                                    {processing ? <Spinner /> : null}
                                    {copy.submitLabel}
                                </Button>
                            </div>
                        </>
                    )}
                </AuthForm>
            </AuthFormCard>

            <AuthSupportFallbackCard visible={showSupportFallback} />
        </>
    );
}
