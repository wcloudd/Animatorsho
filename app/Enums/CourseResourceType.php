<?php

namespace App\Enums;

enum CourseResourceType: string
{
    case Pdf = 'pdf';
    case File = 'file';
    case Image = 'image';
    case ProjectFile = 'project_file';
    case ExternalLink = 'external_link';
}
