import { getAvatarPreset } from '@/lib/avatar-presets';
import { resolvePresetAvatar } from '@/lib/resolve-preset-avatar';
import { ProfileUserAvatar } from '@/components/profile/profile-user-avatar';
import { cn } from '@/lib/utils';

type ProfileAccountStatusCardProps = {
    displayName: string;
    avatarPreset: string | null;
    maskedMobile: string | null;
    hasEmail: boolean;
    hasPassword: boolean;
};

function StatusBadge({
    active,
    activeLabel,
    inactiveLabel,
}: {
    active: boolean;
    activeLabel: string;
    inactiveLabel: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-pill px-2.5 py-1 text-xs font-bold ring-1',
                active
                    ? 'bg-green-soft text-green ring-green/20'
                    : 'bg-purple-soft text-muted ring-purple/10',
            )}
        >
            {active ? activeLabel : inactiveLabel}
        </span>
    );
}

function backupLoginCopy(hasEmail: boolean, hasPassword: boolean): string {
    if (!hasEmail && !hasPassword) {
        return 'ورود اصلی با کد پیامکی است. برای ورود پشتیبان، ایمیل و رمز عبور اضافه کنید.';
    }

    if (!hasEmail) {
        return 'ایمیل ثبت نشده. برای ورود پشتیبان با ایمیل، یک آدرس ایمیل اضافه کنید.';
    }

    if (!hasPassword) {
        return 'رمز عبور تنظیم نشده. برای ورود با ایمیل، یک رمز عبور تعیین کنید.';
    }

    return 'ایمیل و رمز عبور برای ورود پشتیبان فعال است.';
}

export function ProfileAccountStatusCard({
    displayName,
    avatarPreset,
    maskedMobile,
    hasEmail,
    hasPassword,
}: ProfileAccountStatusCardProps) {
    const resolved = resolvePresetAvatar(avatarPreset, displayName);
    const selectedPreset = avatarPreset ? getAvatarPreset(avatarPreset) : undefined;

    return (
        <article className="rounded-[28px] bg-surface px-5 py-6 shadow-soft ring-1 ring-border">
            <div className="flex flex-col gap-5">
                <div className="flex items-center gap-4">
                    <ProfileUserAvatar
                        resolved={resolved}
                        className="size-16 shrink-0 shadow-soft ring-2 ring-purple/20"
                    />
                    <div className="flex min-w-0 flex-1 flex-col gap-2">
                        <span className="text-base font-bold text-text">
                            وضعیت حساب
                        </span>
                        {maskedMobile ? (
                            <p
                                className="text-sm font-medium text-muted"
                                dir="ltr"
                            >
                                {maskedMobile}
                            </p>
                        ) : null}
                        <p className="text-xs font-medium text-muted">
                            {selectedPreset
                                ? `آواتار: ${selectedPreset.labelFa}`
                                : 'آواتار پیش‌فرض انتخاب نشده'}
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <StatusBadge
                        active={hasEmail}
                        activeLabel="ایمیل ثبت شده"
                        inactiveLabel="ایمیل ثبت نشده"
                    />
                    <StatusBadge
                        active={hasPassword}
                        activeLabel="رمز عبور تنظیم شده"
                        inactiveLabel="رمز عبور تنظیم نشده"
                    />
                </div>

                <p className="rounded-2xl bg-gold-soft px-4 py-3 text-sm font-medium leading-relaxed text-text ring-1 ring-gold/20">
                    {backupLoginCopy(hasEmail, hasPassword)}
                </p>
            </div>
        </article>
    );
}
