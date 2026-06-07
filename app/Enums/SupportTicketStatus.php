<?php

namespace App\Enums;

enum SupportTicketStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case WaitingUser = 'waiting_user';
    case Closed = 'closed';
}
