import type { AvatarPresetKey } from '@/lib/avatar-presets';
import { AVATAR_PRESET_KEYS } from '@/lib/avatar-presets';
import { ProfilePresetAvatarCard } from '@/components/profile/profile-preset-avatar-card';

type ProfilePresetAvatarGridProps = {
    value: AvatarPresetKey | null;
    onChange: (key: AvatarPresetKey | null) => void;
};

export function ProfilePresetAvatarGrid({
    value,
    onChange,
}: ProfilePresetAvatarGridProps) {
    return (
        <div className="grid grid-cols-4 gap-2 sm:grid-cols-4">
            {AVATAR_PRESET_KEYS.map((presetKey) => (
                <ProfilePresetAvatarCard
                    key={presetKey}
                    presetKey={presetKey}
                    selected={value === presetKey}
                    onSelect={(key) => onChange(value === key ? null : key)}
                />
            ))}
        </div>
    );
}
