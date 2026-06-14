<?php

namespace App\Enums;

enum ExerciseSubmissionStatus: string
{
    case Submitted = 'submitted';
    case Reviewing = 'reviewing';
    case NeedsRevision = 'needs_revision';
    case Approved = 'approved';

    public function isPendingReview(): bool
    {
        return $this === self::Submitted || $this === self::Reviewing;
    }
}
