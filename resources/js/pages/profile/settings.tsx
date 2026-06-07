import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import { ProfileAccountStatusCard } from '@/components/profile/profile-account-status-card';
import { ProfilePresetAvatarGrid } from '@/components/profile/profile-preset-avatar-grid';
import { ProfileSettingsHeader } from '@/components/profile/profile-settings-header';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { PageContainer } from '@/components/page-container';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AvatarPresetKey } from '@/lib/avatar-presets';
import { isAvatarPresetKey } from '@/lib/avatar-presets';

type AccountProps = {
    name: string;
    email: string | null;
    avatarPreset: string | null;
    maskedMobile: string | null;
    hasEmail: boolean;
    hasPassword: boolean;
};

type AvatarPresetOption = {
    key: string;
    label: string;
};

type ProfileSettingsProps = {
    account: AccountProps;
    avatarPresets: AvatarPresetOption[];
    passwordRules: string;
};

const fieldClassName =
    'border-input bg-surface text-text placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm text-start shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

export default function ProfileSettings({
    account,
    passwordRules,
}: ProfileSettingsProps) {
    const initialPreset = isAvatarPresetKey(account.avatarPreset)
        ? account.avatarPreset
        : null;
    const [selectedPreset, setSelectedPreset] = useState<AvatarPresetKey | null>(
        initialPreset,
    );

    return (
        <>
            <Head title="تنظیمات حساب" />
            <PageContainer>
                <div className="flex flex-col gap-6">
                    <ProfileSettingsHeader />

                    <ProfileAccountStatusCard
                        displayName={account.name}
                        avatarPreset={selectedPreset ?? account.avatarPreset}
                        maskedMobile={account.maskedMobile}
                        hasEmail={account.hasEmail}
                        hasPassword={account.hasPassword}
                    />

                    <section className="rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border">
                        <div className="mb-5 flex flex-col gap-1">
                            <h2 className="text-base font-bold text-text">
                                اطلاعات پروفایل
                            </h2>
                            <p className="text-sm font-medium text-muted">
                                نام، ایمیل و آواتار خود را به‌روزرسانی کنید.
                            </p>
                        </div>

                        <Form
                            {...ProfileController.update.form()}
                            options={{ preserveScroll: true }}
                            className="flex flex-col gap-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">نام نمایشی</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={account.name}
                                            required
                                            maxLength={80}
                                            autoComplete="name"
                                            className={fieldClassName}
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">ایمیل (اختیاری)</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={account.email ?? ''}
                                            autoComplete="username"
                                            placeholder="برای ورود جایگزین"
                                            dir="ltr"
                                            className={fieldClassName}
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-3">
                                        <Label>آواتار</Label>
                                        <ProfilePresetAvatarGrid
                                            value={selectedPreset}
                                            onChange={setSelectedPreset}
                                        />
                                        <input
                                            type="hidden"
                                            name="avatar_preset"
                                            value={selectedPreset ?? ''}
                                        />
                                        <InputError message={errors.avatar_preset} />
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        data-test="update-profile-button"
                                    >
                                        ذخیره اطلاعات
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>

                    <section className="rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border">
                        <div className="mb-5 flex flex-col gap-1">
                            <h2 className="text-base font-bold text-text">
                                {account.hasPassword
                                    ? 'تغییر رمز عبور'
                                    : 'تنظیم رمز عبور'}
                            </h2>
                            <p className="text-sm font-medium text-muted">
                                {account.hasPassword
                                    ? 'برای امنیت بیشتر، رمز عبور قوی انتخاب کنید.'
                                    : 'برای ورود با ایمیل، یک رمز عبور تنظیم کنید.'}
                            </p>
                        </div>

                        <Form
                            {...SecurityController.update.form()}
                            options={{ preserveScroll: true }}
                            resetOnError={[
                                'password',
                                'password_confirmation',
                                'current_password',
                            ]}
                            resetOnSuccess
                            className="flex flex-col gap-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {account.hasPassword ? (
                                        <div className="grid gap-2">
                                            <Label htmlFor="current_password">
                                                رمز عبور فعلی
                                            </Label>
                                            <PasswordInput
                                                id="current_password"
                                                name="current_password"
                                                autoComplete="current-password"
                                                className={fieldClassName}
                                            />
                                            <InputError
                                                message={errors.current_password}
                                            />
                                        </div>
                                    ) : null}

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            {account.hasPassword
                                                ? 'رمز عبور جدید'
                                                : 'رمز عبور'}
                                        </Label>
                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            autoComplete="new-password"
                                            passwordrules={passwordRules}
                                            className={fieldClassName}
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation">
                                            تکرار رمز عبور
                                        </Label>
                                        <PasswordInput
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            autoComplete="new-password"
                                            passwordrules={passwordRules}
                                            className={fieldClassName}
                                        />
                                        <InputError
                                            message={errors.password_confirmation}
                                        />
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        data-test="update-password-button"
                                    >
                                        ذخیره رمز عبور
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                </div>
            </PageContainer>
        </>
    );
}
