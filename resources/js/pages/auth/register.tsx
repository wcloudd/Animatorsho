import { Form, Head } from '@inertiajs/react';
import { AuthFormCard, authFieldClassName, authLabelClassName } from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthPageHeader } from '@/components/auth/auth-page-header';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    AUTH_REGISTER_COPY,
    AUTH_REGISTER_TRUST_NOTE,
} from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules }: Props) {
    const copy = AUTH_REGISTER_COPY;

    return (
        <>
            <Head title={copy.headTitle} />

            <AuthPageHeader title={copy.title} subtitle={copy.subtitle} />

            <AuthFormCard>
                <Form
                    {...store.form()}
                    resetOnSuccess={['password', 'password_confirmation']}
                    disableWhileProcessing
                    className="flex flex-col gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-5">
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
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="name"
                                        name="name"
                                        placeholder={copy.namePlaceholder}
                                        className={authFieldClassName}
                                    />
                                    <AuthInputError message={errors.name} />
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
                                        type="email"
                                        required
                                        tabIndex={2}
                                        autoComplete="email"
                                        name="email"
                                        placeholder={copy.emailPlaceholder}
                                        dir="ltr"
                                        className={authFieldClassName}
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
                                        required
                                        tabIndex={3}
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
                                        required
                                        tabIndex={4}
                                        autoComplete="new-password"
                                        name="password_confirmation"
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
                                    className={cn(
                                        'btn-cta-green h-12 w-full rounded-pill text-sm font-bold text-white',
                                    )}
                                    tabIndex={5}
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
                                    href={login()}
                                    className="text-sm font-bold text-purple"
                                    tabIndex={6}
                                >
                                    {copy.secondaryLinkLabel}
                                </TextLink>
                            </div>
                        </>
                    )}
                </Form>
            </AuthFormCard>

            <p className="text-center text-xs font-medium leading-relaxed text-muted">
                {AUTH_REGISTER_TRUST_NOTE}
            </p>

            <AuthSupportFallbackCard />
        </>
    );
}
