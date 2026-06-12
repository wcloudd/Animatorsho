<?php

namespace App\Enums;

enum AdminUserLookupPreviewStatus: string
{
    case Empty = 'empty';
    case Found = 'found';
    case NotFound = 'not_found';
    case NeedsMobile = 'needs_mobile';
    case Invalid = 'invalid';
}
