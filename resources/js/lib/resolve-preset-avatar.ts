import {
    getAvatarPreset,
    isAvatarPresetKey,
    type AvatarPresetDefinition,
} from '@/lib/avatar-presets';

export type ResolvedAvatar =
    | { kind: 'placeholder'; preset: AvatarPresetDefinition }
    | { kind: 'static'; src: string; preset: AvatarPresetDefinition }
    | { kind: 'animated'; src: string; format: 'lottie' | 'dotlottie'; preset: AvatarPresetDefinition }
    | { kind: 'initials'; name: string };

export function resolvePresetAvatar(
    avatarPreset: string | null | undefined,
    displayName: string,
): ResolvedAvatar {
    if (!isAvatarPresetKey(avatarPreset)) {
        return { kind: 'initials', name: displayName };
    }

    const preset = getAvatarPreset(avatarPreset);

    if (!preset) {
        return { kind: 'initials', name: displayName };
    }

    if (preset.dotLottieSrc) {
        return { kind: 'animated', src: preset.dotLottieSrc, format: 'dotlottie', preset };
    }

    if (preset.lottieSrc) {
        return { kind: 'animated', src: preset.lottieSrc, format: 'lottie', preset };
    }

    if (preset.staticSrc) {
        return { kind: 'static', src: preset.staticSrc, preset };
    }

    return { kind: 'placeholder', preset };
}
