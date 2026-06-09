import { Form, Head } from '@inertiajs/react';
import {
    AuthFormCard,
    authFieldClassName,
    authLabelClassName,
    authSubmitButtonClassName,
} from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthPageIntro } from '@/components/auth/auth-page-intro';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { AUTH_RESET_PASSWORD_COPY } from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';
import { update } from '@/routes/password';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

export default function ResetPassword({ token, email, passwordRules }: Props) {
    const copy = AUTH_RESET_PASSWORD_COPY;

    return (
        <>
            <Head title={copy.headTitle} />

            <AuthPageIntro title={copy.title} subtitle={copy.subtitle} />

            <AuthFormCard>
                <Form
                    {...update.form()}
                    transform={(data) => ({ ...data, token, email })}
                    resetOnSuccess={['password', 'password_confirmation']}
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
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
                                    type="email"
                                    name="email"
                                    autoComplete="email"
                                    defaultValue={email}
                                    readOnly
                                    dir="ltr"
                                    className={cn(
                                        authFieldClassName,
                                        'bg-purple-soft/40',
                                    )}
                                />
                                <AuthInputError message={errors.email} />
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
                                data-test="reset-password-button"
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
