<?php

return [

    'attachment_max_kb' => (int) env('EXERCISE_SUBMISSION_ATTACHMENT_MAX_KB', 5120),

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
