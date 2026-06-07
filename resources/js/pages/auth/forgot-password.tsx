import { Form, Head } from '@inertiajs/react';
import { AuthFormCard, authFieldClassName, authLabelClassName } from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthPageHeader } from '@/components/auth/auth-page-header';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { AUTH_FORGOT_PASSWORD_COPY } from '@/lib/auth-form-data';
import { localizeAuthStatus } from '@/lib/auth-validation-messages';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    const copy = AUTH_FORGOT_PASSWORD_COPY;
    const localizedStatus = localizeAuthStatus(status);

    return (
        <>
            <Head title={copy.headTitle} />

            <AuthPageHeader title={copy.title} subtitle={copy.subtitle} />

            {localizedStatus ? (
                <p className="rounded-2xl bg-green-soft px-4 py-3 text-center text-sm font-medium leading-relaxed text-green">
                    {localizedStatus}
                </p>
            ) : null}

            <AuthFormCard>
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
                                    autoComplete="off"
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
                                {copy.submitLabel}
                            </Button>

                            <div className="flex flex-col items-center gap-1 text-center">
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
                        </>
                    )}
                </Form>
            </AuthFormCard>

            <AuthSupportFallbackCard />
        </>
    );
}
