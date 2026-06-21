<?php

namespace App\Models;

use App\Enums\ExerciseSubmissionStatus;
use Database\Factories\ExerciseSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'title',
    'description',
    'submission_url',
    'file_path',
    'attachment_disk',
    'attachment_path',
    'attachment_original_name',
    'attachment_mime_type',
    'attachment_size_bytes',
    'attachment_deleted_at',
    'attachment_deleted_by',
    'status',
    'admin_feedback',
    'reviewed_by',
    'reviewed_at',
])]
class ExerciseSubmission extends Model
{
    /** @use HasFactory<ExerciseSubmissionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExerciseSubmissionStatus::class,
            'reviewed_at' => 'datetime',
            'attachment_deleted_at' => 'datetime',
            'attachment_size_bytes' => 'integer',
        ];
    }

    public function hasActiveAttachment(): bool
    {
        if ($this->attachment_path !== null
            && $this->attachment_disk !== null
            && $this->attachment_deleted_at === null) {
            return true;
        }

        if ($this->relationLoaded('attachments')) {
            return $this->attachments->contains(fn (ExerciseSubmissionAttachment $attachment): bool => $attachment->isActive());
        }

        return $this->attachments()->whereNull('deleted_at')->exists();
    }

    public function attachmentWasDeleted(): bool
    {
        return $this->attachment_deleted_at !== null
            && $this->attachment_original_name !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function attachmentDeleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attachment_deleted_by');
    }

    /**
     * @return HasMany<ExerciseSubmissionAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ExerciseSubmissionAttachment::class);
    }

    /**
     * @return HasMany<ExerciseSubmissionAttachment, $this>
     */
    public function activeAttachments(): HasMany
    {
        return $this->attachments()->whereNull('deleted_at');
    }

    /**
     * @return HasMany<ExerciseSubmissionFeedbackAttachment, $this>
     */
    public function feedbackAttachments(): HasMany
    {
        return $this->hasMany(ExerciseSubmissionFeedbackAttachment::class);
    }

    /**
     * @return HasMany<ExerciseSubmissionFeedbackAttachment, $this>
     */
    public function activeFeedbackAttachments(): HasMany
    {
        return $this->feedbackAttachments()->whereNull('deleted_at');
    }
}
