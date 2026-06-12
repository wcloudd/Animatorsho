<?php

namespace App\Enums;

enum ExternalEnrollmentSource: string
{
    case Eitaa = 'eitaa';
    case Offline = 'offline';
    case Manual = 'manual';
    case AdminGrant = 'admin_grant';
}
