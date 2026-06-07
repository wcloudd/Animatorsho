<?php

use App\Support\AvatarPresetRegistry;

test('avatar preset registry exposes all expected keys', function () {
    expect(AvatarPresetRegistry::keys())->toHaveCount(8)
        ->toContain('keyframe_happy', 'robot_helper');
});

test('avatar preset registry validates keys', function () {
    expect(AvatarPresetRegistry::isValid('keyframe_happy'))->toBeTrue()
        ->and(AvatarPresetRegistry::isValid('invalid_key'))->toBeFalse()
        ->and(AvatarPresetRegistry::isValid(null))->toBeFalse();
});

test('avatar preset registry returns persian labels', function () {
    expect(AvatarPresetRegistry::label('keyframe_happy'))->toBe('Keyframe شاد');
});

test('avatar preset registry for frontend returns key label pairs', function () {
    $presets = AvatarPresetRegistry::forFrontend();

    expect($presets)->toHaveCount(8)
        ->and($presets[0])->toHaveKeys(['key', 'label']);
});
