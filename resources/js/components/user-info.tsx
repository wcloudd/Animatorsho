import { ProfileUserAvatar } from '@/components/profile/profile-user-avatar';
import { resolvePresetAvatar } from '@/lib/resolve-preset-avatar';
import type { User } from '@/types';

export function UserInfo({
    user,
    showEmail = false,
}: {
    user: User;
    showEmail?: boolean;
}) {
    return (
        <>
            <ProfileUserAvatar
                resolved={resolvePresetAvatar(user.avatar_preset, user.name)}
                className="h-8 w-8 overflow-hidden rounded-full"
                fallbackClassName="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white text-xs"
                emojiClassName="text-base"
            />
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">{user.name}</span>
                {showEmail && user.email ? (
                    <span className="truncate text-xs text-muted-foreground">
                        {user.email}
                    </span>
                ) : null}
            </div>
        </>
    );
}
