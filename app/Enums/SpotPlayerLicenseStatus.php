<?php

namespace App\Enums;

enum SpotPlayerLicenseStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Failed = 'failed';
    case Revoked = 'revoked';
}
