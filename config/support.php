<?php

return [

    'attachment_max_kb' => (int) env('SUPPORT_ATTACHMENT_MAX_KB', 5120),

    'attachment_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'zip'],

];
