<?php

namespace App\Models;

use Database\Factories\ExerciseSubmissionFeedbackAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'exercise_submission_id',
    'uploaded_by',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size_bytes',
    'deleted_at',
    'deleted_by',
])]
class ExerciseSubmissionFeedbackAttachment extends Model
{
    /** @use HasFactory<ExerciseSubmissionFeedbackAttachmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->deleted_at === null;
    }

    /**
     * @return BelongsTo<ExerciseSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(ExerciseSubmission::class, 'exercise_submission_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
