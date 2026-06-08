import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AuthFormCard, authFieldClassName, authLabelClassName } from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthPageHeader } from '@/components/auth/auth-page-header';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { SeoHead } from '@/components/seo/seo-head';
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

    return (
        <>
            <SeoHead
                title={meta.title}
                description={meta.description}
                canonical={canonicalFromPath(appUrl, '/forgot-password')}
            />

            <AuthPageHeader title={copy.title} subtitle={copy.subtitle} />

            {localizedStatus ? (
                <p className="rounded-2xl bg-green-soft px-4 py-3 text-center text-sm font-medium leading-relaxed text-green">
                    {localizedStatus}
                </p>
            ) : null}

            <div className="flex gap-2 rounded-pill bg-purple-soft/60 p-1">
                <button
                    type="button"
                    onClick={() => setMethod('mobile')}
                    className={cn(
                        'flex-1 rounded-pill px-3 py-2.5 text-xs font-bold transition-colors',
                        method === 'mobile'
                            ? 'bg-surface text-purple shadow-sm'
                            : 'text-muted',
                    )}
                    data-test="forgot-password-mobile-tab"
                >
                    {copy.mobileTabLabel}
                </button>
                <button
                    type="button"
                    onClick={() => setMethod('email')}
                    className={cn(
                        'flex-1 rounded-pill px-3 py-2.5 text-xs font-bold transition-colors',
                        method === 'email'
                            ? 'bg-surface text-purple shadow-sm'
                            : 'text-muted',
                    )}
                    data-test="forgot-password-email-tab"
                >
                    {copy.emailTabLabel}
                </button>
            </div>

            <AuthFormCard>
                {method === 'mobile' ? (
                    <>
                        {!smsAvailable ? (
                            <p className="rounded-2xl bg-gold-soft px-4 py-3 text-center text-sm font-medium leading-relaxed text-text">
                                {copy.smsUnavailableMessage}
                            </p>
                        ) : (
                            <p className="text-center text-sm font-medium leading-relaxed text-muted">
                                {copy.mobileSubtitle}
                            </p>
                        )}

                        <Form {...sendCode.form()} className="flex flex-col gap-5">
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
                                        className={cn(
                                            'btn-cta-green h-12 w-full rounded-pill text-sm font-bold text-white',
                                        )}
                                        disabled={processing || !smsAvailable}
                                        data-test="mobile-password-reset-send-button"
                                    >
                                        {processing ? <Spinner /> : null}
                                        {copy.mobileSubmitLabel}
                                    </Button>

                                    <p className="text-center text-sm font-medium leading-relaxed text-muted">
                                        {copy.emailFallbackHint}{' '}
                                        <button
                                            type="button"
                                            onClick={() => setMethod('email')}
                                            className="font-bold text-purple"
                                        >
                                            {copy.emailTabLabel}
                                        </button>
                                    </p>
                                </>
                            )}
                        </Form>
                    </>
                ) : (
                    <>
                        <p className="text-center text-sm font-medium leading-relaxed text-muted">
                            {copy.emailSubtitle}
                        </p>

                        <Form {...email.form()} className="flex flex-col gap-5">
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
                                        className={cn(
                                            'btn-cta-green h-12 w-full rounded-pill text-sm font-bold text-white',
                                        )}
                                        disabled={processing}
                                        data-test="email-password-reset-link-button"
                                    >
                                        {processing ? <Spinner /> : null}
                                        {copy.emailSubmitLabel}
                                    </Button>

                                    {smsAvailable ? (
                                        <p className="text-center text-sm font-medium leading-relaxed text-muted">
                                            <button
                                                type="button"
                                                onClick={() => setMethod('mobile')}
                                                className="font-bold text-purple"
                                            >
                                                {copy.mobileTabLabel}
                                            </button>
                                        </p>
                                    ) : null}
                                </>
                            )}
                        </Form>
                    </>
                )}

                <div className="mt-5 flex flex-col items-center gap-1 text-center">
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

            <AuthSupportFallbackCard />
        </>
    );
}
