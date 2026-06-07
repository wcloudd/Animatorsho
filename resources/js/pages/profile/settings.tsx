import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import { ProfileAccountStatusCard } from '@/components/profile/profile-account-status-card';
import { ProfilePresetAvatarGrid } from '@/components/profile/profile-preset-avatar-grid';
import { ProfileSectionCard } from '@/components/profile/profile-section-card';
import { ProfileSettingsHeader } from '@/components/profile/profile-settings-header';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { PageContainer } from '@/components/page-container';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AvatarPresetKey } from '@/lib/avatar-presets';
import { isAvatarPresetKey } from '@/lib/avatar-presets';
import {
    userFieldClassName,
    userLabelClassName,
    userSubmitButtonClassName,
} from '@/lib/user-form-styles';

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

                    <ProfileSectionCard
                        title="اطلاعات پروفایل"
                        description="نام، ایمیل و آواتار خود را به‌روزرسانی کنید."
                    >
                        <Form
                            {...ProfileController.update.form()}
                            options={{ preserveScroll: true }}
                            className="flex flex-col gap-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="name"
                                            className={userLabelClassName}
                                        >
                                            نام نمایشی
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={account.name}
                                            required
                                            maxLength={80}
                                            autoComplete="name"
                                            className={userFieldClassName}
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="email"
                                            className={userLabelClassName}
                                        >
                                            ایمیل (اختیاری)
                                        </Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={account.email ?? ''}
                                            autoComplete="username"
                                            placeholder="برای ورود جایگزین"
                                            dir="ltr"
                                            className={userFieldClassName}
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-3">
                                        <Label className={userLabelClassName}>
                                            آواتار
                                        </Label>
                                        <ProfilePresetAvatarGrid
                                            value={selectedPreset}
                                            onChange={setSelectedPreset}
                                        />
                                        <input
                                            type="hidden"
                                            name="avatar_preset"
                                            value={selectedPreset ?? ''}
                                        />
                                        <InputError
                                            message={errors.avatar_preset}
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        data-test="update-profile-button"
                                        className={userSubmitButtonClassName}
                                    >
                                        ذخیره اطلاعات
                                    </button>
                                </>
                            )}
                        </Form>
                    </ProfileSectionCard>

                    <ProfileSectionCard
                        title={
                            account.hasPassword
                                ? 'تغییر رمز عبور'
                                : 'تنظیم رمز عبور'
                        }
                        description={
                            account.hasPassword
                                ? 'برای امنیت بیشتر، رمز عبور قوی انتخاب کنید.'
                                : 'برای ورود با ایمیل، یک رمز عبور تنظیم کنید.'
                        }
                    >
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
                                            <Label
                                                htmlFor="current_password"
                                                className={userLabelClassName}
                                            >
                                                رمز عبور فعلی
                                            </Label>
                                            <PasswordInput
                                                id="current_password"
                                                name="current_password"
                                                autoComplete="current-password"
                                                className={userFieldClassName}
                                            />
                                            <InputError
                                                message={
                                                    errors.current_password
                                                }
                                            />
                                        </div>
                                    ) : null}

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password"
                                            className={userLabelClassName}
                                        >
                                            {account.hasPassword
                                                ? 'رمز عبور جدید'
                                                : 'رمز عبور'}
                                        </Label>
                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            autoComplete="new-password"
                                            passwordrules={passwordRules}
                                            className={userFieldClassName}
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password_confirmation"
                                            className={userLabelClassName}
                                        >
                                            تکرار رمز عبور
                                        </Label>
                                        <PasswordInput
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            autoComplete="new-password"
                                            passwordrules={passwordRules}
                                            className={userFieldClassName}
                                        />
                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        data-test="update-password-button"
                                        className={userSubmitButtonClassName}
                                    >
                                        ذخیره رمز عبور
                                    </button>
                                </>
                            )}
                        </Form>
                    </ProfileSectionCard>
                </div>
            </PageContainer>
        </>
    );
}
