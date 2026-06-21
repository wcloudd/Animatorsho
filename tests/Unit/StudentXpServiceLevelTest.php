<?php

function levelFor(int $totalXp): array
{
    $xpPerLevel = 500;
    $level = (int) floor($totalXp / $xpPerLevel) + 1;
    $currentLevelXp = $totalXp % $xpPerLevel;
    $xpToNextLevel = $xpPerLevel - $currentLevelXp;
    $progressPercent = (int) floor(($currentLevelXp / $xpPerLevel) * 100);

    return compact('totalXp', 'level', 'currentLevelXp', 'xpPerLevel', 'xpToNextLevel', 'progressPercent');
}

test('0 XP is level 1 with 0 progress', function () {
    expect(levelFor(0))->toMatchArray([
        'totalXp' => 0,
        'level' => 1,
        'currentLevelXp' => 0,
        'xpPerLevel' => 500,
        'xpToNextLevel' => 500,
        'progressPercent' => 0,
    ]);
});

test('150 XP is level 1 with 30% progress', function () {
    expect(levelFor(150))->toMatchArray([
        'totalXp' => 150,
        'level' => 1,
        'currentLevelXp' => 150,
        'xpPerLevel' => 500,
        'xpToNextLevel' => 350,
        'progressPercent' => 30,
    ]);
});

test('500 XP is level 2 with 0 progress', function () {
    expect(levelFor(500))->toMatchArray([
        'totalXp' => 500,
        'level' => 2,
        'currentLevelXp' => 0,
        'xpPerLevel' => 500,
        'xpToNextLevel' => 500,
        'progressPercent' => 0,
    ]);
});

test('750 XP is level 2 with 50% progress', function () {
    expect(levelFor(750))->toMatchArray([
        'totalXp' => 750,
        'level' => 2,
        'currentLevelXp' => 250,
        'xpPerLevel' => 500,
        'xpToNextLevel' => 250,
        'progressPercent' => 50,
    ]);
});

test('1000 XP is level 3 with 0 progress', function () {
    expect(levelFor(1000))->toMatchArray([
        'totalXp' => 1000,
        'level' => 3,
        'currentLevelXp' => 0,
        'xpPerLevel' => 500,
        'xpToNextLevel' => 500,
        'progressPercent' => 0,
    ]);
});
