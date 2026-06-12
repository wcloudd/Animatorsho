import { Form, Head, router } from '@inertiajs/react';
import { AuthFormCard, authFieldClassName, authLabelClassName } from '@/components/auth/auth-form-card';
import { AuthInputError } from '@/components/auth/auth-input-error';
import { AuthPageHeader } from '@/components/auth/auth-page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { PROFILE_MOBILE_VERIFY_COPY } from '@/lib/auth-form-data';
import { cn } from '@/lib/utils';
import { sendCode, sendExistingCode } from '@/routes/profile/mobile';

type Props = {
    status?: string;
    message?: string;
    existingMobile?: string | null;
    maskedExistingMobile?: string | null;
};

export default function ProfileMobileVerification({
    status,
    message,
    existingMobile = null,
    maskedExistingMobile = null,
}: Props) {
    const copy = PROFILE_MOBILE_VERIFY_COPY;
    const showSentStatus = status === 'otp-sent';
    const bannerMessage =
        status === 'mobile-verification-required' ? copy.requiredMessage : message;
    const hasExistingMobile = Boolean(existingMobile);
    const subtitle = hasExistingMobile ? copy.existingSubtitle : copy.subtitle;

    const handleVerifyExisting = () => {
        router.post(sendExistingCode.url(), {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title={copy.headTitle} />

            <AuthPageHeader title={copy.title} subtitle={subtitle} />

            {bannerMessage ? (
                <p className="rounded-2xl bg-purple-soft px-4 py-3 text-center text-sm font-medium leading-relaxed text-purple">
                    {bannerMessage}
                </p>
            ) : null}

            {showSentStatus ? (
                <p className="rounded-2xl bg-green-soft px-4 py-3 text-center text-sm font-medium leading-relaxed text-green">
                    {copy.sentStatus}
                </p>
            ) : null}

            <AuthFormCard>
                {hasExistingMobile ? (
                    <div className="flex flex-col gap-5">
                        <div
                            className="rounded-2xl bg-purple-soft px-4 py-4 text-center"
                            data-test="profile-existing-mobile-display"
                        >
                            <p className="text-sm font-medium text-muted">
                                {copy.mobileLabel}
                            </p>
                            <p
                                className="mt-1 text-lg font-bold text-text"
                                dir="ltr"
                            >
                                {maskedExistingMobile ?? existingMobile}
                            </p>
                        </div>

                        <Button
                            type="button"
                            className={cn(
                                'btn-cta-green h-12 w-full rounded-pill text-sm font-bold text-white',
                            )}
                            onClick={handleVerifyExisting}
                            data-test="profile-mobile-verify-existing-button"
                        >
                            {copy.verifyExistingLabel}
                        </Button>

                        <p className="text-center text-xs leading-relaxed text-muted">
                            برای تغییر شماره موبایل با پشتیبانی تماس بگیرید.
                        </p>
                    </div>
                ) : (
                    <Form {...sendCode.form()} className="flex flex-col gap-5">
                        {({ processing, errors }) => (
                            <div className="grid gap-5">
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
                                    data-test="profile-mobile-send-button"
                                >
                                    {processing ? <Spinner /> : null}
                                    {copy.submitLabel}
                                </Button>
                            </div>
                        )}
                    </Form>
                )}
            </AuthFormCard>
        </>
    );
}
