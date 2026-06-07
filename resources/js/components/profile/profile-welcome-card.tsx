import { Link } from '@inertiajs/react';
import type { ProfileUser } from '@/lib/profile-data';
import { resolvePresetAvatar } from '@/lib/resolve-preset-avatar';
import { ProfileUserAvatar } from '@/components/profile/profile-user-avatar';
import { Button } from '@/components/ui/button';

type ProfileWelcomeCardProps = {
    user: ProfileUser;
};

function firstNameFromDisplayName(displayName: string): string {
    const parts = displayName.trim().split(/\s+/).filter(Boolean);

    return parts[0] ?? displayName;
}

function contactLine(user: ProfileUser): string {
    if (user.email) {
        return user.email;
    }

    if (user.maskedMobile) {
        return user.maskedMobile;
    }

    return 'ایمیل ثبت نشده';
}

export function ProfileWelcomeCard({ user }: ProfileWelcomeCardProps) {
    const firstName = firstNameFromDisplayName(user.displayName);
    const resolved = resolvePresetAvatar(user.avatarPreset, user.displayName);
    const contact = contactLine(user);

    return (
        <article className="relative overflow-hidden rounded-[28px] bg-surface shadow-soft ring-1 ring-border">
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-gradient-to-bl from-purple-soft via-surface to-gold-soft/50"
            />
            <div
                aria-hidden
                className="pointer-events-none absolute -start-8 -top-6 size-32 rounded-full bg-purple/10 blur-2xl"
            />
            <div
                aria-hidden
                className="pointer-events-none absolute -end-6 -bottom-10 size-36 rounded-full bg-gold/20 blur-2xl"
            />

            <div className="relative flex flex-col gap-5 px-5 py-6">
                <div className="flex items-center gap-4">
                    <ProfileUserAvatar
                        resolved={resolved}
                        className="size-[4.5rem] shrink-0 shadow-soft ring-2 ring-purple/20"
                        fallbackClassName="text-lg"
                    />

                    <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                        <span className="inline-flex w-fit items-center rounded-pill bg-purple-soft px-2.5 py-0.5 text-[11px] font-bold text-purple ring-1 ring-purple/15">
                            پروفایل من
                        </span>
                        <h1 className="font-display text-[1.375rem] leading-tight font-bold text-text">
                            سلام، {firstName}!
                        </h1>
                        <p
                            className="truncate text-xs font-medium text-muted"
                            dir={user.email ? 'ltr' : undefined}
                            title={contact}
                        >
                            {contact}
                        </p>
                    </div>
                </div>

                <p className="rounded-2xl bg-bg px-4 py-3 text-sm font-medium leading-relaxed text-muted ring-1 ring-border/60">
                    وضعیت ثبت‌نام، سفارش‌ها و لایسنس‌های دوره‌ات اینجا نمایش
                    داده می‌شود.
                </p>

                <Button
                    asChild
                    variant="outline"
                    className="w-full border-border bg-surface text-text hover:bg-purple-soft"
                    data-test="profile-settings-link"
                >
                    <Link href={user.settingsUrl}>تنظیمات حساب</Link>
                </Button>
            </div>
        </article>
    );
}
