<?php

namespace App\Services;

use App\Models\StudentNotification;
use App\Models\User;
use App\Support\JalaliDateFormatter;
use Illuminate\Support\Collection;

class StudentNotificationService
{
    public const string TYPE_MEDAL_AWARDED = 'medal_awarded';

    public const string TYPE_EXERCISE_REVIEWED = 'exercise_reviewed';

    public const string TYPE_TEACHER_FEEDBACK_ADDED = 'teacher_feedback_added';

    public const string TYPE_TEACHER_FEEDBACK_ATTACHMENT_ADDED = 'teacher_feedback_attachment_added';

    public const string TYPE_ADMIN_MESSAGE = 'admin_message';

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): StudentNotification
    {
        return StudentNotification::create(array_merge(['user_id' => $user->id], $data));
    }

    /**
     * Create or refresh a notification for a source.
     * If an unread notification already exists for this type+source, update its title/body.
     * If the existing notification was already read, create a new one.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertForSource(
        User $user,
        string $type,
        string $sourceType,
        int $sourceId,
        array $data,
    ): StudentNotification {
        $existing = StudentNotification::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereNull('read_at')
            ->latest()
            ->first();

        if ($existing instanceof StudentNotification) {
            $existing->update($data);

            return $existing->fresh();
        }

        return $this->create($user, array_merge($data, [
            'type' => $type,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]));
    }

    /**
     * Create a notification for a source only if none of this type+source exists yet.
     *
     * @param  array<string, mixed>  $data
     */
    public function createOnceForSource(
        User $user,
        string $type,
        string $sourceType,
        int $sourceId,
        array $data,
    ): ?StudentNotification {
        $exists = StudentNotification::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();

        if ($exists) {
            return null;
        }

        return $this->create($user, array_merge($data, [
            'type' => $type,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]));
    }

    public function unreadCountForUser(User $user): int
    {
        return StudentNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return Collection<int, StudentNotification>
     */
    public function latestForUser(User $user, int $limit = 5): Collection
    {
        return StudentNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function markRead(StudentNotification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllReadForUser(User $user): void
    {
        StudentNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function createAdminMessage(
        User $student,
        string $title,
        string $body,
        ?string $actionUrl,
        User $admin,
    ): StudentNotification {
        return $this->create($student, [
            'type' => self::TYPE_ADMIN_MESSAGE,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'meta' => ['sent_by' => $admin->id],
        ]);
    }

    /**
     * @return array{
     *     unreadCount: int,
     *     items: list<array{id: int, type: string, title: string, body: ?string, actionLabel: ?string, actionUrl: ?string, readAt: ?string, createdAtLabel: string, isUnread: bool}>
     * }
     */
    public function notificationsForHome(User $user): array
    {
        $items = $this->latestForUser($user, 5);

        return [
            'unreadCount' => $this->unreadCountForUser($user),
            'items' => $items->map(fn (StudentNotification $n): array => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'actionLabel' => $n->action_label,
                'actionUrl' => $n->action_url,
                'readAt' => $n->read_at?->toIso8601String(),
                'createdAtLabel' => JalaliDateFormatter::publishedAtLabelWithTime($n->created_at),
                'isUnread' => $n->read_at === null,
            ])->values()->all(),
        ];
    }
}
