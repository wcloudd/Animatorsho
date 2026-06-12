import { Head, usePage } from '@inertiajs/react';
import { AuthForm } from '@/components/auth/auth-form';
import { Lock } from 'lucide-react';
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
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useAuthSupportFallback } from '@/hooks/use-auth-support-fallback';
import { AUTH_MOBILE_COPY } from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';
import { login, register } from '@/routes';
import { sendCode } from '@/routes/auth/mobile';

function redirectQueryFromUrl(url: string): { redirect: string } | undefined {
    const query = url.includes('?') ? url.split('?')[1] : '';
    const redirect = new URLSearchParams(query).get('redirect');

    return redirect ? { redirect } : undefined;
}

type Props = {
    status?: string;
};

export default function MobileAuth({ status }: Props) {
    const copy = AUTH_MOBILE_COPY;
    const showSentStatus = status === 'otp-sent';
    const { url } = usePage();
    const redirectQuery = redirectQueryFromUrl(url);
    const { showSupportFallback, onAuthError } = useAuthSupportFallback();

    return (
        <>
            <Head title={copy.headTitle} />

            <AuthPageIntro title={copy.title} subtitle={copy.subtitle} />

            {showSentStatus ? (
                <AuthStatusBanner message={copy.sentStatus} />
            ) : null}

            <AuthFormCard>
                <AuthForm
                    {...sendCode.form()}
                    onError={onAuthError}
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
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="tel"
                                        inputMode="numeric"
                                        placeholder={copy.mobilePlaceholder}
                                        dir="ltr"
                                        className={authFieldClassName}
                                    />
                                    <AuthInputError message={errors.mobile} />
                                </div>

                                <Button
                                    type="submit"
                                    className={cn(authSubmitButtonClassName)}
                                    tabIndex={2}
                                    disabled={processing}
                                    data-test="mobile-otp-send-button"
                                >
                                    {processing ? <Spinner /> : null}
                                    {copy.submitLabel}
                                </Button>
                            </div>

                            <AuthSecondaryActionCard
                                href={login(
                                    redirectQuery
                                        ? { query: redirectQuery }
                                        : undefined,
                                )}
                                label={copy.secondaryLinkLabel}
                                icon={Lock}
                                tabIndex={3}
                            />

                            <div className="flex flex-col items-center gap-1 text-center">
                                <TextLink
                                    href={register(
                                        redirectQuery
                                            ? { query: redirectQuery }
                                            : undefined,
                                    )}
                                    className="text-sm font-bold text-purple"
                                    tabIndex={4}
                                >
                                    {copy.registerLinkLabel}
                                </TextLink>
                            </div>
                        </>
                    )}
                </AuthForm>
            </AuthFormCard>

            <AuthSupportFallbackCard visible={showSupportFallback} />
        </>
    );
}
