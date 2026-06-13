<?php

namespace App\Enums;

enum CourseUpdateStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
