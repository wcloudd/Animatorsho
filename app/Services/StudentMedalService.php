<?php

namespace App\Services;

use App\Models\StudentMedalAward;
use App\Models\User;
use App\Support\JalaliDateFormatter;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StudentMedalService
{
    /** @var array<string, string> */
    public const MEDALS = [
        'first_story_written' => 'نوشتن اولین داستان',
        'first_storyboard' => 'طراحی اولین استوری‌بورد',
        'first_animation' => 'ساخت اولین انیمیشن',
        'first_approved_exercise' => 'اولین تمرین تأیید شده',
        'ten_approved_exercises' => '۱۰ تمرین تأیید شده',
        'twenty_approved_exercises' => '۲۰ تمرین تأیید شده',
    ];

    /**
     * All predefined medals as [{key, title}].
     *
     * @return list<array{key: string, title: string}>
     */
    public function medals(): array
    {
        return array_map(
            fn (string $key, string $title): array => ['key' => $key, 'title' => $title],
            array_keys(self::MEDALS),
            array_values(self::MEDALS),
        );
    }

    public function award(User $student, string $medalKey, User $admin, ?string $note): StudentMedalAward
    {
        if (! array_key_exists($medalKey, self::MEDALS)) {
            throw ValidationException::withMessages(['medal_key' => 'مدال نامعتبر است.']);
        }

        $existing = StudentMedalAward::query()
            ->where('user_id', $student->id)
            ->where('medal_key', $medalKey)
            ->first();

        if ($existing instanceof StudentMedalAward) {
            throw ValidationException::withMessages(['medal_key' => 'این مدال قبلاً به این هنرجو داده شده است.']);
        }

        return StudentMedalAward::create([
            'user_id' => $student->id,
            'medal_key' => $medalKey,
            'awarded_by' => $admin->id,
            'awarded_at' => now(),
            'note' => $note,
        ]);
    }

    public function revoke(StudentMedalAward $award): void
    {
        $award->delete();
    }

    /**
     * @return Collection<int, StudentMedalAward>
     */
    public function earnedForUser(User $user): Collection
    {
        return StudentMedalAward::query()
            ->where('user_id', $user->id)
            ->orderBy('awarded_at')
            ->get();
    }

    /**
     * @return Collection<int, StudentMedalAward>
     */
    public function recentAwards(int $limit = 25): Collection
    {
        return StudentMedalAward::query()
            ->with(['user', 'awarder'])
            ->latest('awarded_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Returns medals split into earned/locked for the student course home.
     *
     * @return array{
     *     earned: list<array{key: string, title: string, earnedAtLabel: string}>,
     *     locked: list<array{key: string, title: string}>,
     *     totalAvailable: int
     * }
     */
    public function medalsPreviewForUser(User $user): array
    {
        $earned = $this->earnedForUser($user)->keyBy('medal_key');

        $earnedList = [];
        $lockedList = [];

        foreach (self::MEDALS as $key => $title) {
            if ($earned->has($key)) {
                $earnedList[] = [
                    'key' => $key,
                    'title' => $title,
                    'earnedAtLabel' => JalaliDateFormatter::publishedAtLabel($earned[$key]->awarded_at),
                ];
            } else {
                $lockedList[] = ['key' => $key, 'title' => $title];
            }
        }

        return [
            'earned' => $earnedList,
            'locked' => $lockedList,
            'totalAvailable' => count(self::MEDALS),
        ];
    }
}
