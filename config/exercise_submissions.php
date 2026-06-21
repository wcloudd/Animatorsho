<?php

return [

    'attachment_max_kb' => (int) env('EXERCISE_SUBMISSION_ATTACHMENT_MAX_KB', 5120),

    'max_attachments_per_submission' => (int) env('EXERCISE_SUBMISSION_MAX_ATTACHMENTS', 5),

    'max_feedback_attachments_per_submission' => (int) env('EXERCISE_SUBMISSION_MAX_FEEDBACK_ATTACHMENTS', 3),

    'attachment_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'pdf',
        'txt',
        'zip',
        'rar',
        'fla',
        'aep',
        'psd',
        'ai',
        'mp4',
        'webm',
    ],

];
