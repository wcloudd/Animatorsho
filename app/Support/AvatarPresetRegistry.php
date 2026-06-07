<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class AvatarPresetRegistry
{
    /**
     * @var array<string, array{label: string}>
     */
    private const PRESETS = [
        'keyframe_happy' => [
            'label' => 'Keyframe شاد',
        ],
        'keyframe_sleepy' => [
            'label' => 'Keyframe خواب‌آلود',
        ],
        'keyframe_artist' => [
            'label' => 'Keyframe هنرمند',
        ],
        'keyframe_teacher' => [
            'label' => 'Keyframe مربی',
        ],
        'nimvajabee_smile' => [
            'label' => 'نیم‌وجبی خندان',
        ],
        'nimvajabee_glasses' => [
            'label' => 'نیم‌وجبی با عینک',
        ],
        'animator_student' => [
            'label' => 'دانشجوی انیماتور',
        ],
        'robot_helper' => [
            'label' => 'ربات کمک‌کننده',
        ],
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::PRESETS);
    }

    public static function isValid(?string $key): bool
    {
        if ($key === null || $key === '') {
            return false;
        }

        return array_key_exists($key, self::PRESETS);
    }

    public static function label(string $key): string
    {
        return self::PRESETS[$key]['label'] ?? $key;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function all(): array
    {
        return array_map(
            static fn (string $key, array $preset): array => [
                'key' => $key,
                'label' => $preset['label'],
            ],
            array_keys(self::PRESETS),
            self::PRESETS,
        );
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function forFrontend(): array
    {
        return self::all();
    }

    public static function validationRule(): In
    {
        return Rule::in(self::keys());
    }
}
