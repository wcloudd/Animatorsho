import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    AuthFormCard,
    authFieldClassName,
    authLabelClassName,
    authSubmitButtonClassName,
} from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthPageIntro } from '@/components/auth/auth-page-intro';
import { AuthRecoveryMethodCard } from '@/components/auth/auth-recovery-method-card';
import { AuthStatusBanner } from '@/components/auth/auth-status-banner';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { SeoHead } from '@/components/seo/seo-head';
import { useAuthSupportFallback } from '@/hooks/use-auth-support-fallback';
import { AUTH_FORGOT_PASSWORD_COPY } from '@/lib/auth-form-data';
import { localizeAuthStatus } from '@/lib/auth-validation-messages';
import { PUBLIC_PAGE_SEO, canonicalFromPath } from '@/lib/seo';
import { cn } from '@/lib/utils';
import type { SharedPageProps } from '@/types/seo';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { sendCode } from '@/routes/password/mobile';

type RecoveryMethod = 'mobile' | 'email';

type Props = {
    status?: string;
    smsAvailable: boolean;
};

export default function ForgotPassword({ status, smsAvailable }: Props) {
    const copy = AUTH_FORGOT_PASSWORD_COPY;
    const localizedStatus = localizeAuthStatus(status);
    const { appUrl } = usePage<SharedPageProps>().props;
    const meta = PUBLIC_PAGE_SEO.forgotPassword;
    const [method, setMethod] = useState<RecoveryMethod>(
        smsAvailable ? 'mobile' : 'email',
    );
    const { showSupportFallback, onAuthError } = useAuthSupportFallback();

    return (
        <>
            <SeoHead
                title={meta.title}
                description={meta.description}
                canonical={canonicalFromPath(appUrl, '/forgot-password')}
            />

            <AuthPageIntro title={copy.title} subtitle={copy.subtitle} />

            {localizedStatus ? (
                <AuthStatusBanner message={localizedStatus} />
            ) : null}

            <div className="flex gap-2">
                <AuthRecoveryMethodCard
                    label={copy.mobileTabLabel}
                    selected={method === 'mobile'}
                    onSelect={() => setMethod('mobile')}
                    data-test="forgot-password-mobile-tab"
                />
                <AuthRecoveryMethodCard
                    label={copy.emailTabLabel}
                    selected={method === 'email'}
                    onSelect={() => setMethod('email')}
                    data-test="forgot-password-email-tab"
                />
            </div>

            <AuthFormCard>
                {method === 'mobile' ? (
                    <>
                        {!smsAvailable ? (
                            <AuthStatusBanner
                                message={copy.smsUnavailableMessage}
                                variant="warning"
                            />
                        ) : (
                            <p className="text-center text-sm font-medium leading-relaxed text-muted">
                                {copy.mobileSubtitle}
                            </p>
                        )}

                        <Form
                            {...sendCode.form()}
                            onError={onAuthError}
                            className="flex flex-col gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
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
                                            autoComplete="tel"
                                            autoFocus={smsAvailable}
                                            placeholder={copy.mobilePlaceholder}
                                            dir="ltr"
                                            disabled={!smsAvailable}
                                            className={authFieldClassName}
                                        />
                                        <AuthInputError message={errors.mobile} />
                                    </div>

                                    <Button
                                        type="submit"
                                        className={cn(authSubmitButtonClassName)}
                                        disabled={processing || !smsAvailable}
                                        data-test="mobile-password-reset-send-button"
                                    >
                                        {processing ? <Spinner /> : null}
                                        {copy.mobileSubmitLabel}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </>
                ) : (
                    <>
                        <p className="text-center text-sm font-medium leading-relaxed text-muted">
                            {copy.emailSubtitle}
                        </p>

                        <Form
                            {...email.form()}
                            onError={onAuthError}
                            className="flex flex-col gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="email"
                                            className={authLabelClassName}
                                        >
                                            {copy.emailLabel}
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            autoComplete="email"
                                            autoFocus
                                            placeholder={copy.emailPlaceholder}
                                            dir="ltr"
                                            className={authFieldClassName}
                                        />
                                        <AuthInputError message={errors.email} />
                                    </div>

                                    <Button
                                        type="submit"
                                        className={cn(authSubmitButtonClassName)}
                                        disabled={processing}
                                        data-test="email-password-reset-link-button"
                                    >
                                        {processing ? <Spinner /> : null}
                                        {copy.emailSubmitLabel}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </>
                )}

                <div className="mt-4 flex flex-col items-center gap-1 border-t border-border/80 pt-4 text-center">
                    <p className="text-sm font-medium text-muted">
                        {copy.secondaryPrompt}
                    </p>
                    <TextLink
                        href={login()}
                        className="text-sm font-bold text-purple"
                    >
                        {copy.secondaryLinkLabel}
                    </TextLink>
                </div>
            </AuthFormCard>

            <AuthSupportFallbackCard visible={showSupportFallback} />
        </>
    );
}
