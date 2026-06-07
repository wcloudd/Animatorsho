import type { AvatarPresetKey } from '@/lib/avatar-presets';
import { getAvatarPreset } from '@/lib/avatar-presets';
import { resolvePresetAvatar } from '@/lib/resolve-preset-avatar';
import { ProfileUserAvatar } from '@/components/profile/profile-user-avatar';
import { cn } from '@/lib/utils';

type ProfilePresetAvatarCardProps = {
    presetKey: AvatarPresetKey;
    selected: boolean;
    onSelect: (key: AvatarPresetKey) => void;
    size?: 'sm' | 'md';
};

export function ProfilePresetAvatarCard({
    presetKey,
    selected,
    onSelect,
    size = 'md',
}: ProfilePresetAvatarCardProps) {
    const preset = getAvatarPreset(presetKey);

    if (!preset) {
        return null;
    }

    const resolved = resolvePresetAvatar(presetKey, preset.labelFa);
    const avatarSize = size === 'sm' ? 'size-12' : 'size-14';

    return (
        <button
            type="button"
            onClick={() => onSelect(presetKey)}
            aria-pressed={selected}
            aria-label={preset.labelFa}
            className={cn(
                'flex flex-col items-center gap-2 rounded-2xl p-2 text-center transition-all',
                selected
                    ? 'bg-purple-soft ring-2 ring-purple shadow-soft'
                    : 'bg-surface ring-1 ring-border hover:bg-bg',
            )}
        >
            <ProfileUserAvatar
                resolved={resolved}
                className={cn(avatarSize, 'shadow-soft ring-2 ring-white/80')}
            />
            <span className="line-clamp-2 text-[11px] font-semibold leading-snug text-text">
                {preset.labelFa}
            </span>
        </button>
    );
}
