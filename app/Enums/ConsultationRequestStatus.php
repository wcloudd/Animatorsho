<?php

namespace App\Enums;

enum ConsultationRequestStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case FollowUp = 'follow_up';
    case Converted = 'converted';
    case Cancelled = 'cancelled';
}
