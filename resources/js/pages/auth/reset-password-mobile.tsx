import { Form, Head } from '@inertiajs/react';
import {
    AuthFormCard,
    authFieldClassName,
    authLabelClassName,
    authSubmitButtonClassName,
} from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthPageHeader } from '@/components/auth/auth-page-header';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { AUTH_RESET_PASSWORD_MOBILE_COPY } from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';
import { store as resetStore } from '@/routes/password/mobile/reset';

type Props = {
    maskedMobile?: string | null;
    passwordRules: string;
};

export default function ResetPasswordMobile({
    maskedMobile,
    passwordRules,
}: Props) {
    const copy = AUTH_RESET_PASSWORD_MOBILE_COPY;
    const subtitle = maskedMobile
        ? copy.subtitle.replace('{mobile}', maskedMobile)
        : copy.subtitle.replace('{mobile}', '');

    return (
        <>
            <Head title={copy.headTitle} />

            <AuthPageHeader title={copy.title} subtitle={subtitle} />

            <AuthFormCard>
                <Form
                    {...resetStore.form()}
                    resetOnSuccess={['password', 'password_confirmation']}
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="password"
                                    className={authLabelClassName}
                                >
                                    {copy.passwordLabel}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    autoComplete="new-password"
                                    autoFocus
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
                                    name="password_confirmation"
                                    autoComplete="new-password"
                                    placeholder={copy.passwordConfirmPlaceholder}
                                    passwordrules={passwordRules}
                                    className={authFieldClassName}
                                />
                                <AuthInputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className={cn(authSubmitButtonClassName)}
                                disabled={processing}
                                data-test="reset-password-mobile-button"
                            >
                                {processing ? <Spinner /> : null}
                                {copy.submitLabel}
                            </Button>
                        </div>
                    )}
                </Form>
            </AuthFormCard>

            <AuthSupportFallbackCard />
        </>
    );
}
