export const AVATAR_PRESET_KEYS = [
    'keyframe_happy',
    'keyframe_sleepy',
    'keyframe_artist',
    'keyframe_teacher',
    'nimvajabee_smile',
    'nimvajabee_glasses',
    'animator_student',
    'robot_helper',
] as const;

export type AvatarPresetKey = (typeof AVATAR_PRESET_KEYS)[number];

export type AvatarPresetPlaceholder = {
    emoji: string;
    bgClass: string;
    ringClass?: string;
};

export type AvatarPresetDefinition = {
    key: AvatarPresetKey;
    labelFa: string;
    placeholder: AvatarPresetPlaceholder;
    staticSrc?: string;
    lottieSrc?: string;
    dotLottieSrc?: string;
};

export const AVATAR_PRESETS: AvatarPresetDefinition[] = [
    {
        key: 'keyframe_happy',
        labelFa: 'Keyframe شاد',
        placeholder: { emoji: '😊', bgClass: 'bg-purple-soft', ringClass: 'ring-purple/25' },
    },
    {
        key: 'keyframe_sleepy',
        labelFa: 'Keyframe خواب‌آلود',
        placeholder: { emoji: '😴', bgClass: 'bg-gold-soft', ringClass: 'ring-gold/25' },
    },
    {
        key: 'keyframe_artist',
        labelFa: 'Keyframe هنرمند',
        placeholder: { emoji: '🎨', bgClass: 'bg-blue/10', ringClass: 'ring-blue/25' },
    },
    {
        key: 'keyframe_teacher',
        labelFa: 'Keyframe مربی',
        placeholder: { emoji: '📚', bgClass: 'bg-green-soft', ringClass: 'ring-green/25' },
    },
    {
        key: 'nimvajabee_smile',
        labelFa: 'نیم‌وجبی خندان',
        placeholder: { emoji: '🙂', bgClass: 'bg-purple-soft', ringClass: 'ring-purple/25' },
    },
    {
        key: 'nimvajabee_glasses',
        labelFa: 'نیم‌وجبی با عینک',
        placeholder: { emoji: '🤓', bgClass: 'bg-gold-soft', ringClass: 'ring-gold/25' },
    },
    {
        key: 'animator_student',
        labelFa: 'دانشجوی انیماتور',
        placeholder: { emoji: '✏️', bgClass: 'bg-green-soft', ringClass: 'ring-green/25' },
    },
    {
        key: 'robot_helper',
        labelFa: 'ربات کمک‌کننده',
        placeholder: { emoji: '🤖', bgClass: 'bg-blue/10', ringClass: 'ring-blue/25' },
    },
];

const presetMap = new Map(AVATAR_PRESETS.map((preset) => [preset.key, preset]));

export function isAvatarPresetKey(value: string | null | undefined): value is AvatarPresetKey {
    return value !== null && value !== undefined && AVATAR_PRESET_KEYS.includes(value as AvatarPresetKey);
}

export function getAvatarPreset(key: string | null | undefined): AvatarPresetDefinition | undefined {
    if (!isAvatarPresetKey(key)) {
        return undefined;
    }

    return presetMap.get(key);
}

export function assetPathForPreset(key: AvatarPresetKey, filename: 'avatar.lottie' | 'avatar.webp' | 'avatar.json'): string {
    return `/assets/avatars/${key}/${filename}`;
}
