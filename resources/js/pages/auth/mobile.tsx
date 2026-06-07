import { Form, Head, usePage } from '@inertiajs/react';
import { AuthFormCard, authFieldClassName, authLabelClassName } from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthPageHeader } from '@/components/auth/auth-page-header';
import { AuthSupportFallbackCard } from '@/components/auth/auth-support-fallback-card';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
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

    return (
        <>
            <Head title={copy.headTitle} />

            <AuthPageHeader title={copy.title} subtitle={copy.subtitle} />

            {showSentStatus ? (
                <p className="rounded-2xl bg-green-soft px-4 py-3 text-center text-sm font-medium leading-relaxed text-green">
                    {copy.sentStatus}
                </p>
            ) : null}

            <AuthFormCard>
                <Form {...sendCode.form()} className="flex flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="mobile" className={authLabelClassName}>
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
                                        inputMode="numeric"
                                        placeholder={copy.mobilePlaceholder}
                                        dir="ltr"
                                        className={authFieldClassName}
                                    />
                                    <AuthInputError message={errors.mobile} />
                                </div>

                                <Button
                                    type="submit"
                                    className={cn(
                                        'btn-cta-green h-12 w-full rounded-pill text-sm font-bold text-white',
                                    )}
                                    tabIndex={2}
                                    disabled={processing}
                                    data-test="mobile-otp-send-button"
                                >
                                    {processing ? <Spinner /> : null}
                                    {copy.submitLabel}
                                </Button>
                            </div>

                            <div className="flex flex-col items-center gap-3 text-center">
                                <div className="flex flex-col items-center gap-1">
                                    <p className="text-sm font-medium text-muted">
                                        {copy.secondaryPrompt}
                                    </p>
                                    <TextLink
                                        href={login(
                                            redirectQuery ? { query: redirectQuery } : undefined,
                                        )}
                                        className="text-sm font-bold text-purple"
                                        tabIndex={3}
                                    >
                                        {copy.secondaryLinkLabel}
                                    </TextLink>
                                </div>

                                <div className="flex flex-col items-center gap-1">
                                    <p className="text-sm font-medium text-muted">
                                        {copy.registerPrompt}
                                    </p>
                                    <TextLink
                                        href={register(
                                            redirectQuery ? { query: redirectQuery } : undefined,
                                        )}
                                        className="text-sm font-bold text-purple"
                                        tabIndex={4}
                                    >
                                        {copy.registerLinkLabel}
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
