<?php

namespace App\Services;

use App\Enums\ExerciseSubmissionStatus;
use App\Models\ExerciseSubmission;
use App\Models\StudentXpEvent;
use App\Models\User;

class StudentXpService
{
    /**
     * Award, update, or remove XP for an exercise submission.
     *
     * Rules:
     * - approved + xp > 0  → upsert XP event
     * - approved + xp == 0 → delete XP event if it exists
     * - non-approved        → delete XP event if it exists
     */
    public function awardForSubmission(ExerciseSubmission $submission, User $admin, int $xp): void
    {
        if ($submission->status !== ExerciseSubmissionStatus::Approved || $xp <= 0) {
            StudentXpEvent::where('source_type', 'exercise_submission')
                ->where('source_id', $submission->id)
                ->delete();

            return;
        }

        StudentXpEvent::updateOrCreate(
            [
                'source_type' => 'exercise_submission',
                'source_id' => $submission->id,
            ],
            [
                'user_id' => $submission->user_id,
                'points' => $xp,
                'reason' => 'تمرین تأیید شده',
                'awarded_by' => $admin->id,
                'awarded_at' => now(),
            ]
        );
    }

    public function totalXpForUser(User $user): int
    {
        return (int) StudentXpEvent::where('user_id', $user->id)->sum('points');
    }

    /**
     * @return array{
     *     totalXp: int,
     *     level: int,
     *     currentLevelXp: int,
     *     xpPerLevel: int,
     *     xpToNextLevel: int,
     *     progressPercent: int
     * }
     */
    public function levelProgressForUser(User $user): array
    {
        $totalXp = $this->totalXpForUser($user);
        $xpPerLevel = 500;

        $level = (int) floor($totalXp / $xpPerLevel) + 1;
        $currentLevelXp = $totalXp % $xpPerLevel;
        $xpToNextLevel = $xpPerLevel - $currentLevelXp;
        $progressPercent = (int) floor(($currentLevelXp / $xpPerLevel) * 100);

        return [
            'totalXp' => $totalXp,
            'level' => $level,
            'currentLevelXp' => $currentLevelXp,
            'xpPerLevel' => $xpPerLevel,
            'xpToNextLevel' => $xpToNextLevel,
            'progressPercent' => $progressPercent,
        ];
    }
}
