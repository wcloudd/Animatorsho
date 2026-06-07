<?php

namespace App\Enums;

enum SupportTicketMessageSenderType: string
{
    case User = 'user';
    case Admin = 'admin';
    case System = 'system';
}
